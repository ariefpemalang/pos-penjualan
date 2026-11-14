<?php

namespace App\Services;

use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseReportService
{
    /**
     * Ringkasan KPI: Pembelian Hari Ini, Pembelian Bulan Ini, Pembelian Tahun Ini, Pembelian Selama Ini
     *
     * @return array<string, array{count: int, total: float, items_bought?: int}>
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
        $todayQuery = Purchase::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$todayStart, $todayEnd]);

        $todayCount = (int) (clone $todayQuery)->count();
        $todayTotal = (float) (clone $todayQuery)->sum('total');
        $todayItems = (int) DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.status', 'completed')
            ->whereBetween('purchases.created_at', [$todayStart, $todayEnd])
            ->sum('purchase_items.quantity');

        // 2. Bulan Ini
        $monthQuery = Purchase::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$monthStart, $monthEnd]);

        $monthCount = (int) (clone $monthQuery)->count();
        $monthTotal = (float) (clone $monthQuery)->sum('total');
        $monthItems = (int) DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.status', 'completed')
            ->whereBetween('purchases.created_at', [$monthStart, $monthEnd])
            ->sum('purchase_items.quantity');

        // 3. Tahun Ini
        $yearQuery = Purchase::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$yearStart, $yearEnd]);

        $yearCount = (int) (clone $yearQuery)->count();
        $yearTotal = (float) (clone $yearQuery)->sum('total');

        // 4. Selama Ini (All-Time)
        $allQuery = Purchase::query()
            ->where('status', 'completed');

        $allCount = (int) (clone $allQuery)->count();
        $allTotal = (float) (clone $allQuery)->sum('total');

        return [
            'today' => [
                'count' => $todayCount,
                'total' => $todayTotal,
                'items_bought' => $todayItems,
            ],
            'month' => [
                'count' => $monthCount,
                'total' => $monthTotal,
                'items_bought' => $monthItems,
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
     * Laporan Pembelian berdasarkan periode dan filter
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getPurchaseReport(Carbon $from, Carbon $to, array $filters = []): array
    {
        $fromStart = $from->copy()->startOfDay();
        $toEnd = $to->copy()->endOfDay();

        $query = Purchase::query()
            ->with(['user', 'supplier', 'bankAccount', 'items'])
            ->whereBetween('created_at', [$fromStart, $toEnd]);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', 'completed');
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('purchase_no', 'like', $search)
                    ->orWhere('supplier_name', 'like', $search)
                    ->orWhere('notes', 'like', $search)
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('name', 'like', $search);
                    });
            });
        }

        $purchases = (clone $query)->orderByDesc('created_at')->get();

        $count = $purchases->count();
        $totalPurchases = (float) $purchases->sum('total');
        $totalSubtotal = (float) $purchases->sum('subtotal');
        $totalDiscount = (float) $purchases->sum('discount');
        $totalItemsBought = (int) $purchases->sum(fn ($p) => $p->items->sum('quantity'));
        $avgTicket = $count > 0 ? round($totalPurchases / $count, 2) : 0;

        // Breakdown Metode Pembayaran
        $paymentMethods = $purchases->groupBy('payment_method')->map(function (Collection $group, string $method) use ($totalPurchases) {
            $groupTotal = (float) $group->sum('total');
            return [
                'code' => $method,
                'label' => match ($method) {
                    'cash' => 'Tunai',
                    'transfer' => 'Transfer Bank',
                    default => ucfirst($method),
                },
                'count' => $group->count(),
                'total' => $groupTotal,
                'percentage' => $totalPurchases > 0 ? round(($groupTotal / $totalPurchases) * 100, 1) : 0,
            ];
        })->sortByDesc('total')->values();

        // Breakdown Supplier Terbanyak
        $supplierBreakdown = $purchases->groupBy(fn ($p) => $p->displaySupplierName())->map(function (Collection $group, string $supplierName) use ($totalPurchases) {
            $groupTotal = (float) $group->sum('total');
            return [
                'supplier_name' => $supplierName,
                'count' => $group->count(),
                'total' => $groupTotal,
                'percentage' => $totalPurchases > 0 ? round(($groupTotal / $totalPurchases) * 100, 1) : 0,
            ];
        })->sortByDesc('total')->take(5)->values();

        return [
            'purchases' => $purchases,
            'count' => $count,
            'total_purchases' => $totalPurchases,
            'total_subtotal' => $totalSubtotal,
            'total_discount' => $totalDiscount,
            'total_items_bought' => $totalItemsBought,
            'avg_ticket' => $avgTicket,
            'payment_methods' => $paymentMethods,
            'supplier_breakdown' => $supplierBreakdown,
        ];
    }
}
