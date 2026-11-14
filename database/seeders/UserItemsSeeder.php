<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class UserItemsSeeder extends Seeder
{
    /**
     * Data 21 barang dari input user.
     *
     * @return list<array{
     *   code: string,
     *   name: string,
     *   category: string,
     *   unit: string,
     *   stock: int,
     *   min: int,
     *   buy: float,
     *   sell: float
     * }>
     */
    public static function items(): array
    {
        return [
            [
                'code' => 'FLEXI O/F+VOL SAM A217',
                'name' => 'FLEXI O/F+VOL SAM A217',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 0,
                'min' => 1,
                'buy' => 3500,
                'sell' => 5000,
            ],
            [
                'code' => 'TOMBOL LUAR O/F+VOL SAM A217 WHITE',
                'name' => 'TOMBOL LUAR O/F+VOL SAM A217 WHITE',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 0,
                'min' => 1,
                'buy' => 2000,
                'sell' => 3000,
            ],
            [
                'code' => 'FLEXI BOARD REDMI NOTE 10 4G / 10S',
                'name' => 'FLEXI BOARD REDMI NOTE 10 4G / 10S',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 0,
                'min' => 1,
                'buy' => 8000,
                'sell' => 12000,
            ],
            [
                'code' => 'LCD SAMSUNG A105 / M105 YOOHOR',
                'name' => 'LCD SAMSUNG A105 / M105',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 0,
                'min' => 1,
                'buy' => 90000,
                'sell' => 115000,
            ],
            [
                'code' => 'LCD INFINIX SMART 5 / X657 YOOHOR WD',
                'name' => 'LCD INFINIX SMART 5 / X657 YOOHOR WD',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 2,
                'min' => 1,
                'buy' => 90000,
                'sell' => 115000,
            ],
            [
                'code' => 'BRG-20261029-0001',
                'name' => 'Charger MINIMO Micro',
                'category' => 'Charger',
                'unit' => 'pcs',
                'stock' => 0,
                'min' => 0,
                'buy' => 30000,
                'sell' => 45000,
            ],
            [
                'code' => 'BRG-20261023-0001',
                'name' => 'Voucher BYU Unlimited 2hari',
                'category' => 'Voucher',
                'unit' => 'pcs',
                'stock' => 2,
                'min' => 0,
                'buy' => 10000,
                'sell' => 13000,
            ],
            [
                'code' => 'LCD VIVO Y28 OGS',
                'name' => 'LCD VIVO Y28 OGS',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 1,
                'min' => 1,
                'buy' => 95000,
                'sell' => 122000,
            ],
            [
                'code' => 'LCD SAMSUNG A022 SHINESTAR',
                'name' => 'LCD SAMSUNG A022 SHINESTAR',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 0,
                'min' => 1,
                'buy' => 110000,
                'sell' => 140000,
            ],
            [
                'code' => 'LCD OPPO A53 SHINESTAR',
                'name' => 'LCD OPPO A53 SHINESTAR',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 0,
                'min' => 1,
                'buy' => 115000,
                'sell' => 143000,
            ],
            [
                'code' => 'LCD SAMSUNG A03 CORE OGS',
                'name' => 'LCD SAMSUNG A03 CORE OGS',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 0,
                'min' => 1,
                'buy' => 85000,
                'sell' => 107000,
            ],
            [
                'code' => 'LCD SAMSUNG A04E SHINESTAR',
                'name' => 'LCD SAMSUNG A04E SHINESTAR',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 0,
                'min' => 1,
                'buy' => 110000,
                'sell' => 140000,
            ],
            [
                'code' => 'LCD VIVO Y91 XPAS',
                'name' => 'LCD VIVO Y91 XPAS',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 0,
                'min' => 1,
                'buy' => 75000,
                'sell' => 95000,
            ],
            [
                'code' => 'LCD SAMSUNG A04 SHINESTAR',
                'name' => 'LCD SAMSUNG A04 SHINESTAR',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 1,
                'min' => 1,
                'buy' => 108000,
                'sell' => 138000,
            ],
            [
                'code' => 'LCD SAMSUNG A10',
                'name' => 'LCD SAMSUNG A10',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 0,
                'min' => 1,
                'buy' => 105000,
                'sell' => 133000,
            ],
            [
                'code' => 'BRG-20261017-0001',
                'name' => 'Flash Disk Olike 16gb',
                'category' => 'Aksesoris',
                'unit' => 'pcs',
                'stock' => 1,
                'min' => 0,
                'buy' => 90000,
                'sell' => 120000,
            ],
            [
                'code' => 'LCD VIVO Y21A',
                'name' => 'LCD VIVO Y21A',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 0,
                'min' => 1,
                'buy' => 105000,
                'sell' => 136000,
            ],
            [
                'code' => 'LCD OPPO A5 PRO/A60/A3X METO',
                'name' => 'LCD OPPO A5 PRO/A60/A3X METO',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 0,
                'min' => 1,
                'buy' => 92000,
                'sell' => 118000,
            ],
            [
                'code' => 'LCD VIVO Y91 OGS',
                'name' => 'LCD VIVO Y91 OGS',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 2,
                'min' => 1,
                'buy' => 78000,
                'sell' => 100000,
            ],
            [
                'code' => 'LCD VIVO V21 OG',
                'name' => 'LCD VIVO V21 OG',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 1,
                'min' => 1,
                'buy' => 105000,
                'sell' => 136000,
            ],
            [
                'code' => 'PCB TC OPPO A12 2020',
                'name' => 'PCB TC OPPO A12 2020',
                'category' => 'Sparepart HP',
                'unit' => 'pcs',
                'stock' => 1,
                'min' => 1,
                'buy' => 8000,
                'sell' => 13000,
            ],
        ];
    }

    public function run(): void
    {
        $pcs = ItemUnit::firstOrCreate(
            ['abbreviation' => 'pcs'],
            ['name' => 'Pieces']
        );

        $categories = [
            'Charger' => 'Charger smartphone dan adapter',
            'Voucher' => 'Voucher paket data dan pulsa',
            'Aksesoris' => 'Casing, tempered glass, kabel, dan aksesoris',
        ];

        $categoryIds = [];
        foreach ($categories as $catName => $desc) {
            $categoryIds[$catName] = ItemCategory::firstOrCreate(
                ['name' => $catName],
                ['description' => $desc]
            )->id;
        }

        $userId = (int) (User::query()->value('id') ?? 1);
        $stockService = app(StockService::class);

        foreach (self::items() as $row) {
            $item = Item::where('code', $row['code'])
                ->orWhere('name', $row['name'])
                ->first();

            if (! $item) {
                $item = new Item();
                $item->code = $row['code'];
            }

            $catId = $categoryIds[$row['category']] ?? ItemCategory::firstOrCreate(['name' => $row['category']])->id;

            $item->fill([
                'name' => $row['name'],
                'category_id' => $catId,
                'unit_id' => $pcs->id,
                'purchase_price' => $row['buy'],
                'selling_price' => $row['sell'],
                'stock_opname' => $row['min'],
                'is_active' => true,
            ]);
            $item->save();

            // Atur stok jika belum sesuai
            if ((int) $item->stock < $row['stock']) {
                $stockService->stockIn(
                    $item->id,
                    $row['stock'] - (int) $item->stock,
                    $userId,
                    null,
                    'Stok awal barang'
                );
            }
        }
    }
}
