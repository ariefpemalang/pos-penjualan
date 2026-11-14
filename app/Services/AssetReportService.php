<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemCategory;
use Illuminate\Support\Collection;

class AssetReportService
{
    /**
     * Kategori yang termasuk ke grup 'Etalase Toko'.
     */
    public const ETALASE_DEPAN_NAMES = [
        'aksesoris',
        'charger',
        'kabel data',
        'perdana',
        'voucher',
    ];

    /**
     * Kategori yang termasuk ke grup 'Sparepart'.
     */
    public const SPAREPART_NAMES = [];

    /**
     * Pastikan kategori standar ada di database.
     */
    public function ensureStandardCategoriesExist(): void
    {
        $standards = [
            'Aksesoris',
            'Charger',
            'Kabel Data',
            'Perdana',
            'Voucher',
        ];

        foreach ($standards as $name) {
            $exists = ItemCategory::query()
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->exists();

            if (! $exists) {
                ItemCategory::create([
                    'name' => $name,
                    'description' => 'Kategori ' . $name,
                ]);
            }
        }
    }

    /**
     * Menentukan grup dari kategori: 'etalase_depan', 'sparepart', atau 'lainnya'.
     */
    public static function resolveGroup(string $categoryName): string
    {
        $lower = strtolower(trim($categoryName));

        foreach (self::ETALASE_DEPAN_NAMES as $target) {
            if ($lower === $target || str_contains($lower, $target)) {
                return 'etalase_depan';
            }
        }

        foreach (self::SPAREPART_NAMES as $target) {
            if ($lower === $target || str_contains($lower, $target)) {
                return 'sparepart';
            }
        }

        // Jika ada nama sparepart lain seperti 'sparepart laptop'
        if (str_contains($lower, 'sparepart')) {
            return 'sparepart';
        }

        return 'lainnya';
    }

    /**
     * Membangun laporan nilai uang aset per kategori.
     *
     * @param  list<int>  $selectedCategoryIds
     * @return array{
     *   categories: Collection<int, array<string, mixed>>,
     *   summary: array<string, mixed>
     * }
     */
    public function build(array $selectedCategoryIds): array
    {
        $allCategories = ItemCategory::query()->orderBy('name')->get();

        $categoryReports = $allCategories->map(function (ItemCategory $cat) use ($selectedCategoryIds) {
            $isSelected = empty($selectedCategoryIds) || in_array($cat->id, $selectedCategoryIds, true);
            $groupType = self::resolveGroup($cat->name);

            // Data Stok & Uang Aset saat ini
            $items = Item::query()
                ->with('unit:id,name,abbreviation')
                ->where('category_id', $cat->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $itemCount = $items->count();
            $totalStock = (int) $items->sum(fn ($i) => (int) ($i->stock > 0 ? $i->stock : $i->stock_opname));
            $assetCost = (float) $items->sum(fn ($i) => (int) ($i->stock > 0 ? $i->stock : $i->stock_opname) * (float) $i->purchase_price);
            $assetSale = (float) $items->sum(fn ($i) => (int) ($i->stock > 0 ? $i->stock : $i->stock_opname) * (float) $i->selling_price);
            $potentialMargin = round($assetSale - $assetCost, 2);

            // Rincian barang per kategori untuk detail modal
            $itemsDetail = $items->map(function ($i) {
                $qty = (int) ($i->stock > 0 ? $i->stock : $i->stock_opname);
                return [
                    'id' => $i->id,
                    'code' => $i->code,
                    'name' => $i->name,
                    'stock' => $qty,
                    'unit' => $i->unit?->abbreviation ?: ($i->unit?->name ?: 'pcs'),
                    'purchase_price' => (float) $i->purchase_price,
                    'selling_price' => (float) $i->selling_price,
                    'asset_cost' => round($qty * (float) $i->purchase_price, 2),
                    'asset_sale' => round($qty * (float) $i->selling_price, 2),
                    'margin' => round(($qty * (float) $i->selling_price) - ($qty * (float) $i->purchase_price), 2),
                ];
            });

            return [
                'category_id' => $cat->id,
                'name' => $cat->name,
                'description' => $cat->description,
                'group_type' => $groupType,
                'is_selected' => $isSelected,
                'item_count' => $itemCount,
                'total_stock' => $totalStock,
                'asset_cost' => $assetCost,
                'asset_sale' => $assetSale,
                'potential_margin' => $potentialMargin,
                'items' => $itemsDetail,
            ];
        });

        // Sertakan juga barang tanpa kategori jika ada
        $uncategorizedItems = Item::query()
            ->with('unit:id,name,abbreviation')
            ->whereNull('category_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($uncategorizedItems->isNotEmpty()) {
            $uTotalStock = (int) $uncategorizedItems->sum(fn ($i) => (int) ($i->stock > 0 ? $i->stock : $i->stock_opname));
            $uAssetCost = (float) $uncategorizedItems->sum(fn ($i) => (int) ($i->stock > 0 ? $i->stock : $i->stock_opname) * (float) $i->purchase_price);
            $uAssetSale = (float) $uncategorizedItems->sum(fn ($i) => (int) ($i->stock > 0 ? $i->stock : $i->stock_opname) * (float) $i->selling_price);

            $categoryReports->push([
                'category_id' => 0,
                'name' => 'Tanpa Kategori',
                'description' => 'Barang yang belum memiliki kategori',
                'group_type' => 'lainnya',
                'is_selected' => empty($selectedCategoryIds) || in_array(0, $selectedCategoryIds, true),
                'item_count' => $uncategorizedItems->count(),
                'total_stock' => $uTotalStock,
                'asset_cost' => $uAssetCost,
                'asset_sale' => $uAssetSale,
                'potential_margin' => round($uAssetSale - $uAssetCost, 2),
                'items' => $uncategorizedItems->map(function ($i) {
                    $qty = (int) ($i->stock > 0 ? $i->stock : $i->stock_opname);
                    return [
                        'id' => $i->id,
                        'code' => $i->code,
                        'name' => $i->name,
                        'stock' => $qty,
                        'unit' => $i->unit?->abbreviation ?: ($i->unit?->name ?: 'pcs'),
                        'purchase_price' => (float) $i->purchase_price,
                        'selling_price' => (float) $i->selling_price,
                        'asset_cost' => round($qty * (float) $i->purchase_price, 2),
                        'asset_sale' => round($qty * (float) $i->selling_price, 2),
                        'margin' => round(($qty * (float) $i->selling_price) - ($qty * (float) $i->purchase_price), 2),
                    ];
                }),
            ]);
        }

        // Hitung akumulasi grup kategori yang dipilih
        $selectedOnly = $categoryReports->where('is_selected', true);

        // Subgrup: Etalase Toko (di antara kategori terpilih)
        $etalaseDepan = $selectedOnly->where('group_type', 'etalase_depan');
        $sparepartGroup = $selectedOnly->where('group_type', 'sparepart');

        $summary = [
            'selected_categories_count' => $selectedOnly->count(),
            'total_items_count' => (int) $selectedOnly->sum('item_count'),
            'total_stock' => (int) $selectedOnly->sum('total_stock'),
            'total_asset_cost' => (float) $selectedOnly->sum('asset_cost'),
            'total_asset_sale' => (float) $selectedOnly->sum('asset_sale'),
            'total_potential_margin' => (float) $selectedOnly->sum('potential_margin'),

            // Total Etalase Toko
            'total_asset_depan' => (float) $etalaseDepan->sum('asset_cost'),
            'total_asset_sale_depan' => (float) $etalaseDepan->sum('asset_sale'),
            'total_margin_depan' => (float) $etalaseDepan->sum('potential_margin'),
            'total_stock_depan' => (int) $etalaseDepan->sum('total_stock'),
            'total_items_depan' => (int) $etalaseDepan->sum('item_count'),
            'categories_count_depan' => $etalaseDepan->count(),

            // Total Sparepart
            'total_sparepart' => (float) $sparepartGroup->sum('asset_cost'),
            'total_asset_sale_sparepart' => (float) $sparepartGroup->sum('asset_sale'),
            'total_margin_sparepart' => (float) $sparepartGroup->sum('potential_margin'),
            'total_stock_sparepart' => (int) $sparepartGroup->sum('total_stock'),
            'total_items_sparepart' => (int) $sparepartGroup->sum('item_count'),
            'categories_count_sparepart' => $sparepartGroup->count(),
        ];

        return [
            'categories' => $categoryReports,
            'summary' => $summary,
        ];
    }
}
