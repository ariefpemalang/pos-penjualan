<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SalesReportTest extends TestCase
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
    public function super_admin_can_open_sales_reports(): void
    {
        $this->actingAs($this->admin)
            ->get(route('sales-reports.index'))
            ->assertOk()
            ->assertSee('Laporan Penjualan')
            ->assertSee('Transaksi Hari Ini')
            ->assertSee('Penjualan Bulan Ini')
            ->assertSee('Penjualan Tahun Ini')
            ->assertSee('Penjualan Selama Ini');
    }

    #[Test]
    public function super_admin_can_open_item_sales_reports(): void
    {
        $this->actingAs($this->admin)
            ->get(route('item-sales-reports.index'))
            ->assertOk()
            ->assertSee('Laporan Penjualan Barang')
            ->assertSee('Ragam Produk Terjual')
            ->assertSee('Total Unit Terjual');
    }

    #[Test]
    public function super_admin_can_open_category_sales_reports(): void
    {
        $this->actingAs($this->admin)
            ->get(route('category-sales-reports.index'))
            ->assertOk()
            ->assertSee('Laporan Penjualan Kategori')
            ->assertSee('Kategori Terjual')
            ->assertSee('Kontribusi Omset');
    }
}
