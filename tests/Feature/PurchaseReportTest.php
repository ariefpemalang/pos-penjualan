<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseReportTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    #[Test]
    public function super_admin_can_open_purchase_reports(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP-TEST-001',
            'name' => 'Supplier Test',
        ]);

        $category = ItemCategory::create(['name' => 'Kategori Uji']);
        $unit = ItemUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs']);

        $item = Item::create([
            'code' => 'BRG-01',
            'name' => 'Barang Uji Coba',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'stock' => 10,
            'purchase_price' => 50000,
            'selling_price' => 65000,
            'is_active' => true,
        ]);

        $purchase = Purchase::create([
            'purchase_no' => 'PO-TEST-0001',
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'user_id' => $this->admin->id,
            'subtotal' => 100000,
            'discount' => 0,
            'total' => 100000,
            'status' => 'completed',
            'payment_method' => 'cash',
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'item_id' => $item->id,
            'item_code' => $item->code,
            'item_name' => $item->name,
            'quantity' => 2,
            'unit_price' => 50000,
            'subtotal' => 100000,
        ]);

        $this->actingAs($this->admin)
            ->get(route('purchase-reports.index'))
            ->assertOk()
            ->assertSee('Laporan Pembelian')
            ->assertSee('Pembelian Hari Ini')
            ->assertSee('Pembelian Bulan Ini')
            ->assertSee('Pembelian Tahun Ini')
            ->assertSee('Pembelian Selama Ini')
            ->assertSee('PO-TEST-0001')
            ->assertSee('Supplier Test');
    }

    #[Test]
    public function super_admin_can_export_purchase_reports_pdf(): void
    {
        $this->actingAs($this->admin)
            ->get(route('purchase-reports.export-pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    #[Test]
    public function guest_cannot_access_purchase_reports(): void
    {
        $this->get(route('purchase-reports.index'))
            ->assertRedirect(route('login'));
    }
}
