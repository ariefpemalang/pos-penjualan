<?php

namespace App\Services;

use App\Models\ItemCategory;
use App\Models\Transaction;
use App\Support\PaymentMethodResolver;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    /**
     * Ringkasan KPI: Transaksi Hari Ini, Penjualan Bulan Ini, Penjualan Tahun Ini, Penjualan Selama Ini
     *
     * @return array<string, array{count: int, total: float, items_sold?: int}>
     */
    public function getSummaryCards(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $yearStart = now()->startOfYear();
        $yearEnd = now()->endOfYear();

        // 1. Hari Ini
        $todayTx = Transaction::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$todayStart, $todayEnd]);

        $todayCount = (int) (clone $todayTx)->count();
        $todayTotal = (float) (clone $todayTx)->sum('total');

        $todayItemsSold = (int) DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$todayStart, $todayEnd])
            ->sum('transaction_items.quantity');

        // 2. Bulan Ini
        $monthTx = Transaction::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$monthStart, $monthEnd]);

        $monthCount = (int) (clone $monthTx)->count();
        $monthTotal = (float) (clone $monthTx)->sum('total');

        $monthItemsSold = (int) DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$monthStart, $monthEnd])
            ->sum('transaction_items.quantity');

        // 3. Tahun Ini
        $yearTx = Transaction::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$yearStart, $yearEnd]);

        $yearCount = (int) (clone $yearTx)->count();
        $yearTotal = (float) (clone $yearTx)->sum('total');

        // 4. Selama Ini (All-Time)
        $allTx = Transaction::query()
            ->where('status', 'completed');

        $allCount = (int) (clone $allTx)->count();
        $allTotal = (float) (clone $allTx)->sum('total');

        return [
            'today' => [
                'count' => $todayCount,
                'total' => $todayTotal,
                'items_sold' => $todayItemsSold,
            ],
            'month' => [
                'count' => $monthCount,
                'total' => $monthTotal,
                'items_sold' => $monthItemsSold,
            ],
            'year' => [
                'count' => $yearCount,
                'total' => $yearTotal,
            ],
            'all_time' => [
                'count' => $allCount,
                'total' => $allTotal,
            ],
        ];
    }

    /**
     * Laporan Penjualan Transaksi berdasarkan periode
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getSalesReport(Carbon $from, Carbon $to, array $filters = []): array
    {
        $fromStart = $from->copy()->startOfDay();
        $toEnd = $to->copy()->endOfDay();

        $query = Transaction::query()
            ->with(['user', 'items'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$fromStart, $toEnd]);

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('transaction_no', 'like', $search)
                    ->orWhere('customer_name', 'like', $search)
                    ->orWhere('notes', 'like', $search);
            });
        }

        $transactions = (clone $query)->orderByDesc('created_at')->get();

        $txCount = $transactions->count();
        $totalSales = (float) $transactions->sum('total');
        $totalGross = (float) $transactions->sum(fn ($t) => (float) $t->subtotal_items + (float) $t->subtotal_services);
        $totalDiscount = (float) $transactions->sum('discount');

        // Biaya HPP barang
        $itemsCost = (float) DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$fromStart, $toEnd])
            ->selectRaw('COALESCE(SUM(transaction_items.unit_cost * transaction_items.quantity), 0) as v')
            ->value('v');

        $grossProfit = $totalSales - $itemsCost;
        $avgTicket = $txCount > 0 ? round($totalSales / $txCount, 2) : 0;

        // Breakdown Metode Pembayaran
        $paymentMethods = $transactions->groupBy('payment_method')->map(function (Collection $group, string $method) use ($totalSales) {
            $groupTotal = (float) $group->sum('total');
            return [
                'code' => $method,
                'label' => PaymentMethodResolver::label($method),
                'count' => $group->count(),
                'total' => $groupTotal,
                'percentage' => $totalSales > 0 ? round(($groupTotal / $totalSales) * 100, 1) : 0,
            ];
        })->sortByDesc('total')->values();

        // Tren Harian
        $dailyTrends = $transactions->groupBy(fn ($t) => $t->created_at->format('Y-m-d'))->map(function (Collection $group, string $date) {
            return [
                'date' => $date,
                'formatted_date' => Carbon::parse($date)->translatedFormat('d M Y'),
                'count' => $group->count(),
                'total' => (float) $group->sum('total'),
            ];
        })->sortBy('date')->values();

        return [
            'summary' => [
                'count' => $txCount,
                'gross' => $totalGross,
                'discount' => $totalDiscount,
                'total_sales' => $totalSales,
                'total_cost' => $itemsCost,
                'gross_profit' => $grossProfit,
                'avg_ticket' => $avgTicket,
            ],
            'payment_methods' => $paymentMethods,
            'daily_trends' => $dailyTrends,
            'transactions' => $transactions,
        ];
    }

    /**
     * Laporan Penjualan Barang per item dalam periode
     *
     * @return array<string, mixed>
     */
    public function getItemSalesReport(Carbon $from, Carbon $to, ?int $categoryId = null, ?string $search = null): array
    {
        $fromStart = $from->copy()->startOfDay();
        $toEnd = $to->copy()->endOfDay();

        $query = DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->leftJoin('items', 'items.id', '=', 'transaction_items.item_id')
            ->leftJoin('item_categories', 'item_categories.id', '=', 'items.category_id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$fromStart, $toEnd]);

        if ($categoryId) {
            $query->where('items.category_id', $categoryId);
        }

        if ($search) {
            $s = '%' . trim($search) . '%';
            $query->where(function ($q) use ($s) {
                $q->where('items.name', 'like', $s)
                    ->orWhere('items.code', 'like', $s)
                    ->orWhere('transaction_items.item_name', 'like', $s)
                    ->orWhere('transaction_items.item_code', 'like', $s);
            });
        }

        $rawItems = $query->select([
            'transaction_items.item_id',
            DB::raw('COALESCE(items.code, transaction_items.item_code) as item_code'),
            DB::raw('COALESCE(items.name, transaction_items.item_name) as item_name'),
            DB::raw('COALESCE(item_categories.name, "Tanpa Kategori") as category_name'),
            DB::raw('items.category_id'),
            DB::raw('SUM(transaction_items.quantity) as total_qty'),
            DB::raw('SUM(transaction_items.subtotal) as total_sales'),
            DB::raw('SUM(transaction_items.quantity * transaction_items.unit_cost) as total_cost'),
            DB::raw('SUM(transaction_items.subtotal) - SUM(transaction_items.quantity * transaction_items.unit_cost) as gross_profit'),
        ])
        ->groupBy(
            'transaction_items.item_id',
            'items.code',
            'transaction_items.item_code',
            'items.name',
            'transaction_items.item_name',
            'item_categories.name',
            'items.category_id'
        )
        ->orderByDesc('total_sales')
        ->get();

        $totalQty = (int) $rawItems->sum('total_qty');
        $totalSales = (float) $rawItems->sum('total_sales');
        $totalCost = (float) $rawItems->sum('total_cost');
        $totalProfit = (float) $rawItems->sum('gross_profit');
        $avgMargin = $totalSales > 0 ? round(($totalProfit / $totalSales) * 100, 1) : 0;

        $items = $rawItems->map(function ($row) use ($totalSales) {
            $sales = (float) $row->total_sales;
            $cost = (float) $row->total_cost;
            $profit = (float) $row->gross_profit;
            $qty = (int) $row->total_qty;
            $avgPrice = $qty > 0 ? round($sales / $qty, 2) : 0;
            $avgUnitCost = $qty > 0 ? round($cost / $qty, 2) : 0;
            $marginPct = $sales > 0 ? round(($profit / $sales) * 100, 1) : 0;
            $sharePct = $totalSales > 0 ? round(($sales / $totalSales) * 100, 1) : 0;

            return [
                'item_id' => $row->item_id,
                'item_code' => $row->item_code ?? '-',
                'item_name' => $row->item_name ?? 'Produk Dihapus',
                'category_name' => $row->category_name,
                'category_id' => $row->category_id,
                'total_qty' => $qty,
                'avg_price' => $avgPrice,
                'avg_cost' => $avgUnitCost,
                'total_sales' => $sales,
                'total_cost' => $cost,
                'gross_profit' => $profit,
                'margin_percent' => $marginPct,
                'share_percent' => $sharePct,
            ];
        });

        $categories = ItemCategory::query()->orderBy('name')->get(['id', 'name']);

        return [
            'summary' => [
                'unique_items' => $items->count(),
                'total_qty' => $totalQty,
                'total_sales' => $totalSales,
                'total_cost' => $totalCost,
                'gross_profit' => $totalProfit,
                'avg_margin' => $avgMargin,
            ],
            'items' => $items,
            'categories' => $categories,
        ];
    }

    /**
     * Laporan Penjualan Kategori dalam periode
     *
     * @return array<string, mixed>
     */
    public function getCategorySalesReport(Carbon $from, Carbon $to): array
    {
        $fromStart = $from->copy()->startOfDay();
        $toEnd = $to->copy()->endOfDay();

        $rawCategories = DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->leftJoin('items', 'items.id', '=', 'transaction_items.item_id')
            ->leftJoin('item_categories', 'item_categories.id', '=', 'items.category_id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$fromStart, $toEnd])
            ->select([
                DB::raw('COALESCE(item_categories.id, 0) as category_id'),
                DB::raw('COALESCE(item_categories.name, "Tanpa Kategori") as category_name'),
                DB::raw('COUNT(DISTINCT transaction_items.item_id) as items_count'),
                DB::raw('SUM(transaction_items.quantity) as total_qty'),
                DB::raw('SUM(transaction_items.subtotal) as total_sales'),
                DB::raw('SUM(transaction_items.quantity * transaction_items.unit_cost) as total_cost'),
                DB::raw('SUM(transaction_items.subtotal) - SUM(transaction_items.quantity * transaction_items.unit_cost) as gross_profit'),
            ])
            ->groupBy(
                DB::raw('COALESCE(item_categories.id, 0)'),
                DB::raw('COALESCE(item_categories.name, "Tanpa Kategori")')
            )
            ->orderByDesc('total_sales')
            ->get();

        $totalQty = (int) $rawCategories->sum('total_qty');
        $totalSales = (float) $rawCategories->sum('total_sales');
        $totalCost = (float) $rawCategories->sum('total_cost');
        $totalProfit = (float) $rawCategories->sum('gross_profit');
        $avgMargin = $totalSales > 0 ? round(($totalProfit / $totalSales) * 100, 1) : 0;

        $categories = $rawCategories->map(function ($row) use ($totalSales) {
            $sales = (float) $row->total_sales;
            $cost = (float) $row->total_cost;
            $profit = (float) $row->gross_profit;
            $qty = (int) $row->total_qty;
            $marginPct = $sales > 0 ? round(($profit / $sales) * 100, 1) : 0;
            $sharePct = $totalSales > 0 ? round(($sales / $totalSales) * 100, 1) : 0;

            return [
                'category_id' => (int) $row->category_id,
                'category_name' => $row->category_name,
                'items_count' => (int) $row->items_count,
                'total_qty' => $qty,
                'total_sales' => $sales,
                'total_cost' => $cost,
                'gross_profit' => $profit,
                'margin_percent' => $marginPct,
                'share_percent' => $sharePct,
            ];
        });

        return [
            'summary' => [
                'total_categories' => $categories->count(),
                'total_qty' => $totalQty,
                'total_sales' => $totalSales,
                'total_cost' => $totalCost,
                'gross_profit' => $totalProfit,
                'avg_margin' => $avgMargin,
            ],
            'categories' => $categories,
        ];
    }
}
