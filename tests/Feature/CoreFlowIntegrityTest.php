<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\ManualCashEntry;
use App\Models\ServiceTicket;
use App\Models\Technician;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WorkshopService;
use App\Services\FinancialReportService;
use App\Services\ServiceTicketService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CoreFlowIntegrityTest extends TestCase
{
    private User $admin;

    private User $kasir;

    private User $teknisi;

    private Customer $customer;

    private Technician $technician;

    private WorkshopService $service;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create(['email' => 'admin-core@test.local']);
        $this->admin->assignRole('Super Admin');

        $this->kasir = User::factory()->create(['email' => 'kasir-core@test.local']);
        $this->kasir->assignRole('Kasir');

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teknisi']);
        $this->teknisi = User::factory()->create(['email' => 'teknisi-core@test.local']);
        $this->teknisi->assignRole('Teknisi');

        $this->customer = Customer::create([
            'code' => 'PLG-CORE-01',
            'name' => 'Pelanggan Core',
            'phone' => '081234567890',
        ]);

        $this->technician = Technician::create([
            'code' => 'TKN-CORE-01',
            'name' => 'Teknisi Core',
            'commission_percent' => 20,
            'is_active' => true,
        ]);

        $this->service = WorkshopService::create([
            'code' => 'JSV-CORE-01',
            'name' => 'Ganti LCD Core',
            'price' => 150000,
            'is_active' => true,
        ]);

        $category = ItemCategory::create(['name' => 'Sparepart Core']);
        $unit = ItemUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs']);

        $this->item = Item::create([
            'code' => 'BRG-CORE-01',
            'name' => 'LCD Core',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'stock' => 8,
            'purchase_price' => 20000,
            'selling_price' => 50000,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function positive_ticket_dp_invoice_and_financial_report_balance(): void
    {
        $ticket = $this->createTicket([
            'down_payment' => 50000,
            'down_payment_payment_method' => 'cash',
            'estimated_cost' => 200000,
        ]);

        $this->assertSame('received', $ticket->status);
        $this->assertFalse($ticket->canCreateInvoice());
        $this->assertSame(50000.0, $ticket->downPaymentAmount());
        $this->assertStringStartsWith('UMK-', $ticket->downPaymentTransaction->transaction_no);

        app(ServiceTicketService::class)->updateStatus(
            $ticket,
            'ready',
            'Bisa diambil',
            $this->admin->id,
            false,
            [],
            [['workshop_service_id' => $this->service->id, 'quantity' => 1, 'unit_price' => 150000]],
        );

        $this->assertTrue($ticket->fresh()->canCreateInvoice());

        $tx = $this->billTicket($ticket->fresh(), 100000);

        $this->assertSame(50000.0, $tx->downPaymentAmount());
        $this->assertSame(100000.0, (float) $tx->total);
        $this->assertSame(150000.0, $tx->jobTotal());
        $this->assertSame(30000.0, (float) $tx->technician_commission);
        $this->assertSame('completed', $ticket->fresh()->status);
        $this->assertFalse($ticket->fresh()->canCreateInvoice());

        $report = app(FinancialReportService::class)->build(Carbon::today(), Carbon::today());

        $this->assertSame(50000.0, $report['sales']['down_payment_in']);
        $this->assertSame(0.0, $report['sales']['down_payment_refunded']);
        $this->assertSame(150000.0, $report['sales']['revenue']);
        $this->assertSame(150000.0, $report['sales']['services_revenue']);
        $this->assertSame(30000.0, $report['sales']['technician_commission']);
        $this->assertSame(120000.0, $report['sales']['owner_share']);
        $this->assertSame(120000.0, $report['profit']['owner_net_estimate']);
        $this->assertSame(120000.0, $report['profit']['cash_flow_estimate']);
        $this->assertSame(150000.0, $report['payment_sources']['inflows']['cash']);
    }

    #[Test]
    public function positive_sale_cancel_restores_stock_and_drops_from_report(): void
    {
        $tx = app(TransactionService::class)->create([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'payment_method' => 'cash',
            'amount_paid' => 100000,
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
        ], $this->admin->id);

        $this->assertSame(6, $this->item->fresh()->stock);

        app(TransactionService::class)->cancel($tx, $this->admin->id);

        $this->assertSame(8, $this->item->fresh()->stock);
        $this->assertSame('cancelled', $tx->fresh()->status);

        $report = app(FinancialReportService::class)->build(Carbon::today(), Carbon::today());
        $this->assertSame(0, $report['sales']['transaction_count']);
        $this->assertSame(0.0, $report['sales']['revenue']);
    }

    #[Test]
    public function positive_dp_refund_is_expense_and_excluded_from_owner_profit(): void
    {
        $ticket = $this->createTicket([
            'down_payment' => 40000,
            'down_payment_payment_method' => 'cash',
        ]);

        app(ServiceTicketService::class)->updateStatus(
            $ticket,
            'cancelled',
            'Pelanggan batal',
            $this->admin->id,
            false,
        );

        $dp = Transaction::query()->where('is_down_payment', true)->firstOrFail();
        $this->assertTrue($dp->isRefunded());
        $this->assertFalse($dp->isCancelled());
        $this->assertSame(1, ManualCashEntry::query()->where('type', 'expense')->count());
        $this->assertSame(0.0, $ticket->fresh()->downPaymentAmount());

        $report = app(FinancialReportService::class)->build(Carbon::today(), Carbon::today());
        $this->assertSame(40000.0, $report['sales']['down_payment_in']);
        $this->assertSame(40000.0, $report['sales']['down_payment_refunded']);
        $this->assertSame(40000.0, $report['sales']['revenue']);
        $this->assertSame(40000.0, $report['manual_expense']['amount']);
        $this->assertSame(0.0, $report['profit']['owner_net_estimate']);
        $this->assertSame(0.0, $report['profit']['cash_flow_estimate']);
    }

    #[Test]
    public function negative_closed_ticket_cannot_change_status_or_details(): void
    {
        $ticket = $this->createTicket();
        app(ServiceTicketService::class)->updateStatus($ticket, 'cancelled', 'Batal', $this->admin->id, false);

        $this->expectException(InvalidArgumentException::class);
        app(ServiceTicketService::class)->updateStatus($ticket->fresh(), 'in_progress', 'Hack', $this->admin->id, false);
    }

    #[Test]
    public function negative_invalid_status_is_rejected(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->admin)
            ->post(route('service-tickets.status', $ticket), [
                'status' => 'hacked',
                'notify_customer' => 0,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('received', $ticket->fresh()->status);
    }

    #[Test]
    public function negative_cancelled_ticket_cannot_be_billed(): void
    {
        $ticket = $this->createTicket();
        app(ServiceTicketService::class)->updateStatus($ticket, 'cancelled', 'Batal', $this->admin->id, false);

        $this->expectException(InvalidArgumentException::class);
        $this->billTicket($ticket->fresh());
    }

    #[Test]
    public function negative_ticket_cannot_be_billed_twice(): void
    {
        $ticket = $this->createTicket();
        $this->billTicket($ticket);

        $this->expectException(InvalidArgumentException::class);
        $this->billTicket($ticket->fresh());
    }

    #[Test]
    public function negative_down_payment_cannot_change_after_final_invoice(): void
    {
        $ticket = $this->createTicket([
            'down_payment' => 25000,
            'down_payment_payment_method' => 'cash',
        ]);
        $this->billTicket($ticket->fresh(), 125000);

        $this->expectException(InvalidArgumentException::class);
        app(ServiceTicketService::class)->updateDetails($ticket->fresh(), [
            'customer_id' => $this->customer->id,
            'device_type' => 'smartphone',
            'brand' => 'Samsung',
            'model' => 'A54',
            'complaint' => 'LCD pecah',
            'down_payment' => 10000,
            'down_payment_payment_method' => 'cash',
            'warranty_days' => 30,
        ], $this->admin->id);
    }

    #[Test]
    public function negative_down_payment_cannot_be_refunded_twice(): void
    {
        $ticket = $this->createTicket([
            'down_payment' => 25000,
            'down_payment_payment_method' => 'cash',
        ]);
        $dp = $ticket->downPaymentTransaction;

        app(TransactionService::class)->cancel($dp, $this->admin->id);

        $this->expectException(InvalidArgumentException::class);
        app(TransactionService::class)->cancel($dp->fresh(), $this->admin->id);
    }

    #[Test]
    public function negative_down_payment_cannot_be_refunded_after_final_nota(): void
    {
        $ticket = $this->createTicket([
            'down_payment' => 25000,
            'down_payment_payment_method' => 'cash',
        ]);
        $dp = $ticket->downPaymentTransaction;
        $this->billTicket($ticket->fresh(), 125000);

        $this->expectException(InvalidArgumentException::class);
        app(TransactionService::class)->cancel($dp->fresh(), $this->admin->id);
    }

    #[Test]
    public function negative_insufficient_stock_and_cash_roll_back(): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            app(TransactionService::class)->create([
                'customer_mode' => 'existing',
                'customer_id' => $this->customer->id,
                'payment_method' => 'cash',
                'amount_paid' => 1,
                'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
            ], $this->admin->id);
        } finally {
            $this->assertSame(8, $this->item->fresh()->stock);
            $this->assertSame(0, Transaction::query()->where('is_down_payment', false)->count());
        }
    }

    #[Test]
    public function negative_empty_cart_and_negative_price_are_rejected(): void
    {
        $this->actingAs($this->kasir)
            ->postJson(route('transactions.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $this->customer->id,
                'payment_method' => 'cash',
                'amount_paid' => 10000,
            ])
            ->assertStatus(422);

        $this->actingAs($this->kasir)
            ->postJson(route('transactions.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $this->customer->id,
                'payment_method' => 'cash',
                'amount_paid' => 10000,
                'items' => [['item_id' => $this->item->id, 'quantity' => 1, 'unit_price' => -5000]],
            ])
            ->assertStatus(422);

        $this->assertSame(8, $this->item->fresh()->stock);
    }

    #[Test]
    public function pentest_guest_cannot_access_core_admin_pages(): void
    {
        $ticket = $this->createTicket();
        $tx = $this->billTicket($ticket);

        auth()->logout();
        $this->flushSession();

        $this->get(route('service-tickets.index'))->assertRedirect(route('login'));
        $this->get(route('service-tickets.show', $ticket))->assertRedirect(route('login'));
        $this->get(route('service-tickets.claim-slip', $ticket))->assertRedirect(route('login'));
        $this->post(route('service-tickets.status', $ticket), ['status' => 'completed'])->assertRedirect(route('login'));
        $this->get(route('transactions.index'))->assertRedirect(route('login'));
        $this->get(route('transactions.invoice', $tx))->assertRedirect(route('login'));
        $this->get(route('financial-reports.index'))->assertRedirect(route('login'));
        $this->get(route('financial-reports.export-pdf'))->assertRedirect(route('login'));
    }

    #[Test]
    public function pentest_role_permissions_block_unauthorized_actions(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->teknisi)
            ->get(route('financial-reports.index'))
            ->assertForbidden();

        $this->actingAs($this->teknisi)
            ->get(route('transactions.create'))
            ->assertForbidden();

        $this->actingAs($this->teknisi)
            ->post(route('service-tickets.store'), [
                'customer_id' => $this->customer->id,
                'device_type' => 'smartphone',
                'brand' => 'Xiaomi',
                'model' => 'Redmi',
                'complaint' => 'Mati',
            ])
            ->assertForbidden();

        $this->actingAs($this->kasir)
            ->delete(route('service-tickets.destroy', $ticket))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('financial-reports.index'))
            ->assertOk();
    }

    #[Test]
    public function pentest_public_tracking_hides_internal_notes_and_escapes_xss(): void
    {
        $payload = '<script>alert(1)</script>';

        $this->actingAs($this->admin)
            ->post(route('service-tickets.store'), [
                'customer_id' => $this->customer->id,
                'device_type' => 'smartphone',
                'brand' => 'Samsung',
                'model' => 'A54',
                'complaint' => $payload,
                'internal_notes' => 'Catatan internal rahasia',
                'warranty_preset' => '30',
            ])
            ->assertRedirect();

        $ticket = ServiceTicket::query()->firstOrFail();

        $this->get(route('service-tickets.track', $ticket->tracking_token))
            ->assertOk()
            ->assertDontSee('Catatan internal rahasia', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);

        $this->get(route('service-tickets.track', 'not-a-valid-token'))
            ->assertNotFound();

        $this->get(route('service-tickets.track', (string) Str::uuid()))
            ->assertNotFound();
    }

    #[Test]
    public function pentest_mass_assignment_cannot_force_completed_status(): void
    {
        $this->actingAs($this->admin)
            ->post(route('service-tickets.store'), [
                'customer_id' => $this->customer->id,
                'device_type' => 'smartphone',
                'brand' => 'Samsung',
                'model' => 'A54',
                'complaint' => 'LCD pecah',
                'status' => 'completed',
                'ticket_no' => 'TKS-HACK-9999',
                'tracking_token' => 'hack-token',
            ])
            ->assertRedirect();

        $ticket = ServiceTicket::query()->firstOrFail();
        $this->assertSame('received', $ticket->status);
        $this->assertNotSame('TKS-HACK-9999', $ticket->ticket_no);
        $this->assertNotSame('hack-token', $ticket->tracking_token);
        $this->assertStringStartsWith('TKS-', $ticket->ticket_no);
    }

    #[Test]
    public function pentest_invalid_ticket_id_and_report_date_injection_are_rejected(): void
    {
        $this->actingAs($this->kasir)
            ->postJson(route('transactions.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $this->customer->id,
                'payment_method' => 'cash',
                'amount_paid' => 150000,
                'service_ticket_id' => '1 OR 1=1',
                'services' => [['workshop_service_id' => $this->service->id, 'quantity' => 1]],
                'technician_id' => $this->technician->id,
            ])
            ->assertStatus(422);

        $this->actingAs($this->kasir)
            ->postJson(route('transactions.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $this->customer->id,
                'payment_method' => 'cash',
                'amount_paid' => 150000,
                'service_ticket_id' => (string) Str::uuid(),
                'services' => [['workshop_service_id' => $this->service->id, 'quantity' => 1]],
                'technician_id' => $this->technician->id,
            ])
            ->assertStatus(422);

        $this->actingAs($this->admin)
            ->get(route('financial-reports.index', [
                'from' => "2026-01-01' OR 1=1 --",
                'to' => '2026-12-31',
            ]))
            ->assertSessionHasErrors('from');
    }

    #[Test]
    public function race_second_status_change_on_closed_ticket_fails(): void
    {
        $ticket = $this->createTicket();
        $service = app(ServiceTicketService::class);

        $service->updateStatus($ticket, 'cancelled', 'Batal 1', $this->admin->id, false);

        $this->expectException(InvalidArgumentException::class);
        $service->updateStatus($ticket->fresh(), 'cancelled', 'Batal 2', $this->admin->id, false);
    }

    #[Test]
    public function race_cancelling_invoice_reopens_ticket_for_single_rebill(): void
    {
        $ticket = $this->createTicket();
        $first = $this->billTicket($ticket);
        app(TransactionService::class)->cancel($first, $this->admin->id);

        $ticket->refresh();
        $this->assertSame('ready', $ticket->status);
        $this->assertTrue($ticket->canCreateInvoice());

        $second = $this->billTicket($ticket);
        $this->assertSame('completed', $ticket->fresh()->status);
        $this->assertNotSame($first->id, $second->id);

        $this->expectException(InvalidArgumentException::class);
        $this->billTicket($ticket->fresh());
    }

    #[Test]
    public function race_document_numbers_stay_unique_inside_one_request(): void
    {
        $first = $this->createTicket();
        $second = $this->createTicket();

        $this->assertNotSame($first->ticket_no, $second->ticket_no);
        $this->assertNotSame($first->tracking_token, $second->tracking_token);
        $this->assertSame(2, ServiceTicket::query()->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTicket(array $overrides = []): ServiceTicket
    {
        $response = $this->actingAs($this->admin)
            ->post(route('service-tickets.store'), array_merge([
                'customer_id' => $this->customer->id,
                'device_type' => 'smartphone',
                'brand' => 'Samsung',
                'model' => 'A54',
                'complaint' => 'LCD pecah',
                'notify_customer' => 0,
            ], $overrides));

        $response->assertRedirect();

        $ticketId = basename(parse_url($response->headers->get('Location'), PHP_URL_PATH));

        return ServiceTicket::query()->findOrFail($ticketId);
    }

    private function billTicket(ServiceTicket $ticket, float $amountPaid = 150000): Transaction
    {
        if ($ticket->status !== 'ready' && ! $ticket->isClosed()) {
            app(ServiceTicketService::class)->updateStatus(
                $ticket,
                'ready',
                'Bisa diambil',
                $this->admin->id,
                false,
                [],
                [['workshop_service_id' => $this->service->id, 'quantity' => 1, 'unit_price' => (float) $this->service->price]],
            );
            $ticket->refresh();
        } elseif ($ticket->status === 'ready' && ! $ticket->hasWorkLines()) {
            app(ServiceTicketService::class)->updateWorkLines(
                $ticket,
                [],
                [['workshop_service_id' => $this->service->id, 'quantity' => 1, 'unit_price' => (float) $this->service->price]],
            );
            $ticket->refresh();
        }

        return app(TransactionService::class)->create([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'technician_id' => $this->technician->id,
            'service_ticket_id' => $ticket->id,
            'payment_method' => 'cash',
            'amount_paid' => $amountPaid,
            'services' => [['workshop_service_id' => $this->service->id, 'quantity' => 1]],
        ], $this->admin->id);
    }
}
