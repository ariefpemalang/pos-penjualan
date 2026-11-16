<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemExcelService
{
    private const IMPORT_SHEET = 'Import Barang';

    private const REF_CATEGORY_SHEET = 'Referensi Kategori';

    private const REF_UNIT_SHEET = 'Referensi Satuan';

    private const GUIDE_SHEET = 'Panduan';

    private const IMPORT_MAX_ROWS = 500;

    /** @var array<int, string> */
    private const IMPORT_HEADERS = [
        'Nama Barang *',
        'Kategori *',
        'Satuan *',
        'Stok Awal',
        'Harga Beli',
        'Harga Jual',
        'Deskripsi',
        'Aktif (Ya/Tidak)',
    ];

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = $this->buildTemplateSpreadsheet();

        return $this->streamSpreadsheet(
            $spreadsheet,
            'format-import-barang-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function export(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Barang');

        $headers = [
            'Kode', 'Nama Barang', 'Kategori', 'Satuan', 'Stok',
            'Stock Opname', 'Harga Beli', 'Harga Jual', 'Deskripsi', 'Aktif',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $this->styleHeaderRow($sheet, 'A1:J1');

        $items = Item::query()
            ->with(['category:id,name', 'unit:id,name,abbreviation'])
            ->orderBy('name')
            ->get();

        $row = 2;
        foreach ($items as $item) {
            $sheet->fromArray([
                $item->code,
                $item->name,
                $item->category?->name ?? '',
                $this->unitLabel($item->unit),
                (int) $item->stock,
                (int) $item->stock_opname,
                (float) $item->purchase_price,
                (float) $item->selling_price,
                $item->description ?? '',
                $item->is_active ? 'Ya' : 'Tidak',
            ], null, "A{$row}");
            $row++;
        }

        $this->autoSizeColumns($sheet, 'A', 'J');

        return $this->streamSpreadsheet(
            $spreadsheet,
            'export-barang-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    /**
     * @return array{created: int, skipped: int, errors: array<int, string>}
     */
    public function import(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $this->resolveImportSheet($spreadsheet);

        return $this->processImportRows($sheet, [
            'create_missing_masters' => true,
            'max_rows' => self::IMPORT_MAX_ROWS,
            'column_d_as_available_stock' => true,
        ]);
    }

    /**
     * @return array{created: int, skipped: int, errors: array<int, string>}
     */
    public function seedFromPath(string $path): array
    {
        if (! is_file($path)) {
            return [
                'created' => 0,
                'skipped' => 0,
                'errors' => ["File tidak ditemukan: {$path}"],
            ];
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $this->resolveImportSheet($spreadsheet);

        return $this->processImportRows($sheet, [
            'create_missing_masters' => true,
            'max_rows' => null,
            'default_unit_label' => 'Pieces (pcs)',
            'column_d_as_available_stock' => true,
        ]);
    }

    /**
     * Cari sheet yang paling sesuai untuk data import barang.
     */
    private function resolveImportSheet(Spreadsheet $spreadsheet): Worksheet
    {
        // 1. Cek sheet khusus 'Import Barang'
        $sheet = $spreadsheet->getSheetByName(self::IMPORT_SHEET);
        if ($sheet && $this->isDataSheet($sheet)) {
            return $sheet;
        }

        // 2. Cek sheet 'Data Barang' (format export)
        $sheet = $spreadsheet->getSheetByName('Data Barang');
        if ($sheet && $this->isDataSheet($sheet)) {
            return $sheet;
        }

        // 3. Cari dari semua sheet yang ada di file, lewati sheet panduan & referensi
        foreach ($spreadsheet->getAllSheets() as $s) {
            $title = mb_strtolower(trim($s->getTitle()));
            if ($title === mb_strtolower(self::GUIDE_SHEET)
                || $title === mb_strtolower(self::REF_CATEGORY_SHEET)
                || $title === mb_strtolower(self::REF_UNIT_SHEET)) {
                continue;
            }
            if ($this->isDataSheet($s)) {
                return $s;
            }
        }

        // 4. Jika sheet 'Import Barang' ada
        if ($sheet = $spreadsheet->getSheetByName(self::IMPORT_SHEET)) {
            return $sheet;
        }

        // 5. Fallback ke active sheet jika bukan panduan
        $active = $spreadsheet->getActiveSheet();
        if (mb_strtolower(trim($active->getTitle())) !== mb_strtolower(self::GUIDE_SHEET)) {
            return $active;
        }

        // 6. Terakhir, ambil sheet pertama yang bukan panduan
        foreach ($spreadsheet->getAllSheets() as $s) {
            if (mb_strtolower(trim($s->getTitle())) !== mb_strtolower(self::GUIDE_SHEET)) {
                return $s;
            }
        }

        return $active;
    }

    private function isDataSheet(Worksheet $sheet): bool
    {
        $highestCol = min(20, \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn()));
        for ($col = 1; $col <= $highestCol; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $val = mb_strtolower(trim((string) $sheet->getCell("{$colLetter}1")->getValue()));
            if (str_contains($val, 'nama') || str_contains($val, 'barang') || str_contains($val, 'produk') || str_contains($val, 'item')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{
     *     create_missing_masters: bool,
     *     max_rows?: ?int,
     *     default_unit_label?: string,
     *     column_d_as_available_stock?: bool
     * }  $options
     * @return array{created: int, skipped: int, errors: array<int, string>}
     */
    private function processImportRows(Worksheet $sheet, array $options): array
    {
        $createMissingMasters = $options['create_missing_masters'];
        $defaultUnitLabel = $options['default_unit_label'] ?? 'Pcs (pcs)';
        $columnDAsAvailableStock = (bool) ($options['column_d_as_available_stock'] ?? false);
        $stockService = $columnDAsAvailableStock ? app(StockService::class) : null;
        $seedUserId = $columnDAsAvailableStock
            ? (int) (auth()->id() ?? User::query()->value('id') ?? 1)
            : 0;

        $categories = ItemCategory::query()->orderBy('name')->get()->keyBy(
            fn (ItemCategory $c) => mb_strtolower(trim($c->name))
        );

        $units = $this->buildUnitLookup(ItemUnit::query()->orderBy('name')->get());

        $created = 0;
        $skipped = 0;
        $errors = [];
        $highestRow = (int) $sheet->getHighestDataRow();
        $rowLimit = $options['max_rows'] ?? null;

        if ($rowLimit !== null) {
            $highestRow = min($highestRow, $rowLimit + 1);
        }

        // Deteksi mapping kolom secara cerdas berdasarkan teks header di baris 1
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        $headerMap = [
            'name' => null,
            'category' => null,
            'unit' => null,
            'code' => null,
            'stock' => null,
            'stock_opname' => null,
            'purchase_price' => null,
            'selling_price' => null,
            'description' => null,
            'is_active' => null,
        ];

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $headerVal = mb_strtolower(trim((string) $sheet->getCell("{$colLetter}1")->getValue()));
            if ($headerVal === '') {
                continue;
            }

            if (str_contains($headerVal, 'nama') || str_contains($headerVal, 'barang') || str_contains($headerVal, 'produk')) {
                $headerMap['name'] = $colLetter;
            } elseif (str_contains($headerVal, 'kategori') || str_contains($headerVal, 'category')) {
                $headerMap['category'] = $colLetter;
            } elseif (str_contains($headerVal, 'satuan') || str_contains($headerVal, 'unit')) {
                $headerMap['unit'] = $colLetter;
            } elseif (str_contains($headerVal, 'kode') || str_contains($headerVal, 'code') || str_contains($headerVal, 'barcode') || str_contains($headerVal, 'sku')) {
                $headerMap['code'] = $colLetter;
            } elseif (str_contains($headerVal, 'opname') || str_contains($headerVal, 'minimum')) {
                $headerMap['stock_opname'] = $colLetter;
            } elseif (str_contains($headerVal, 'stok') || str_contains($headerVal, 'stock') || str_contains($headerVal, 'qty') || str_contains($headerVal, 'jumlah')) {
                $headerMap['stock'] = $colLetter;
            } elseif (str_contains($headerVal, 'beli') || str_contains($headerVal, 'modal') || str_contains($headerVal, 'hpp')) {
                $headerMap['purchase_price'] = $colLetter;
            } elseif (str_contains($headerVal, 'jual') || str_contains($headerVal, 'harga') || str_contains($headerVal, 'price')) {
                $headerMap['selling_price'] = $colLetter;
            } elseif (str_contains($headerVal, 'deskripsi') || str_contains($headerVal, 'keterangan') || str_contains($headerVal, 'catatan')) {
                $headerMap['description'] = $colLetter;
            } elseif (str_contains($headerVal, 'aktif') || str_contains($headerVal, 'status')) {
                $headerMap['is_active'] = $colLetter;
            }
        }

        // Fallbacks
        $headerMap['name'] ??= 'A';
        $headerMap['category'] ??= 'B';
        $headerMap['unit'] ??= 'C';
        $headerMap['stock'] ??= ($headerMap['stock_opname'] ?? 'D');
        $headerMap['stock_opname'] ??= ($headerMap['stock'] ?? 'D');
        $headerMap['purchase_price'] ??= 'E';
        $headerMap['selling_price'] ??= 'F';
        $headerMap['description'] ??= 'G';
        $headerMap['is_active'] ??= 'H';

        for ($row = 2; $row <= $highestRow; $row++) {
            $colName = $headerMap['name'];
            $colCat = $headerMap['category'];
            $colUnit = $headerMap['unit'];
            $colCode = $headerMap['code'];
            $colStock = $headerMap['stock'];
            $colOpname = $headerMap['stock_opname'];
            $colBuy = $headerMap['purchase_price'];
            $colSell = $headerMap['selling_price'];
            $colDesc = $headerMap['description'];
            $colActive = $headerMap['is_active'];

            $name = trim((string) $sheet->getCell("{$colName}{$row}")->getValue());
            $categoryName = $colCat ? trim((string) $sheet->getCell("{$colCat}{$row}")->getValue()) : '';
            $unitLabel = $colUnit ? trim((string) $sheet->getCell("{$colUnit}{$row}")->getValue()) : '';
            $customCode = $colCode ? trim((string) $sheet->getCell("{$colCode}{$row}")->getValue()) : '';

            // Lewati baris kosong
            if ($name === '' && $categoryName === '' && $unitLabel === '' && $customCode === '') {
                continue;
            }

            if ($name === '') {
                $errors[] = "Baris {$row}: Nama barang wajib diisi.";
                $skipped++;

                continue;
            }

            // Kategori otomatis jika kosong
            if ($categoryName === '') {
                $categoryName = 'Umum';
            }

            $categoryKey = mb_strtolower($categoryName);
            $category = $categories->get($categoryKey);

            if (! $category && $createMissingMasters) {
                $category = ItemCategory::create(['name' => $categoryName]);
                $categories->put($categoryKey, $category);
            }

            if (! $category) {
                $errors[] = "Baris {$row}: Kategori \"{$categoryName}\" tidak ditemukan.";
                $skipped++;

                continue;
            }

            // Satuan otomatis jika kosong
            if ($unitLabel === '') {
                $unitLabel = $defaultUnitLabel;
            }

            $unit = $units->get(mb_strtolower($unitLabel))
                ?? $units->get(mb_strtolower($this->normalizeUnitName($unitLabel)));

            if (! $unit && $createMissingMasters && $unitLabel !== '') {
                $unit = $this->createUnitFromLabel($unitLabel);
                $units->put(mb_strtolower($this->unitLabel($unit)), $unit);
                $units->put(mb_strtolower($unit->name), $unit);

                if ($unit->abbreviation) {
                    $units->put(mb_strtolower($unit->abbreviation), $unit);
                }
            }

            if (! $unit) {
                $unit = ItemUnit::query()->first();
            }

            if (! $unit) {
                $unit = ItemUnit::create(['name' => 'Pieces', 'abbreviation' => 'pcs']);
            }

            $rawStockVal = max(0, $this->parseInt($colStock ? $sheet->getCell("{$colStock}{$row}")->getValue() : 0));
            $rawOpnameVal = max(0, $this->parseInt($colOpname ? $sheet->getCell("{$colOpname}{$row}")->getValue() : 0));
            $rawBuyPrice = max(0, $this->parseNumber($colBuy ? $sheet->getCell("{$colBuy}{$row}")->getValue() : 0));
            $rawSellPrice = max(0, $this->parseNumber($colSell ? $sheet->getCell("{$colSell}{$row}")->getValue() : 0));
            $rawDesc = $colDesc ? (trim((string) $sheet->getCell("{$colDesc}{$row}")->getValue()) ?: null) : null;
            $rawActive = $colActive ? $this->parseYesNo($sheet->getCell("{$colActive}{$row}")->getValue(), true) : true;

            // Cek apakah item sudah ada berdasarkan kode atau nama
            $existingItem = null;
            if ($customCode !== '') {
                $existingItem = Item::withTrashed()->where('code', $customCode)->first();
            }
            if (! $existingItem) {
                $existingItem = Item::withTrashed()->where('name', $name)->first();
            }

            if ($existingItem) {
                $stockDiff = $rawStockVal - (int) $existingItem->stock;
                $existingItem->update([
                    'category_id' => $category->id,
                    'unit_id' => $unit->id,
                    'stock' => $rawStockVal > 0 ? $rawStockVal : $existingItem->stock,
                    'stock_opname' => $rawOpnameVal > 0 ? $rawOpnameVal : $existingItem->stock_opname,
                    'purchase_price' => $rawBuyPrice > 0 ? $rawBuyPrice : $existingItem->purchase_price,
                    'selling_price' => $rawSellPrice > 0 ? $rawSellPrice : $existingItem->selling_price,
                    'description' => $rawDesc ?: $existingItem->description,
                    'is_active' => $rawActive,
                    'deleted_at' => null,
                ]);

                if ($columnDAsAvailableStock && $stockDiff > 0 && $stockService && $seedUserId > 0) {
                    try {
                        $stockService->stockIn(
                            $existingItem->id,
                            $stockDiff,
                            $seedUserId,
                            null,
                            'Penyesuaian stok import Excel'
                        );
                    } catch (\Throwable $e) {
                        // ignore error
                    }
                }

                $created++;

                continue;
            }

            $item = Item::create([
                'code' => ($customCode !== '' && ! Item::where('code', $customCode)->exists()) ? $customCode : Item::generateCode(),
                'name' => $name,
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'stock' => 0,
                'stock_opname' => $rawOpnameVal,
                'purchase_price' => $rawBuyPrice,
                'selling_price' => $rawSellPrice,
                'description' => $rawDesc,
                'is_active' => $rawActive,
            ]);

            if ($columnDAsAvailableStock && $rawStockVal > 0 && $stockService && $seedUserId > 0) {
                try {
                    $stockService->stockIn(
                        $item->id,
                        $rawStockVal,
                        $seedUserId,
                        null,
                        'Stok awal import Excel'
                    );
                } catch (\Throwable $e) {
                    $item->update(['stock' => $rawStockVal]);
                }
            } else {
                $item->update(['stock' => $rawStockVal]);
            }

            $created++;
        }

        return compact('created', 'skipped', 'errors');
    }

    private function createUnitFromLabel(string $label): ItemUnit
    {
        $name = $this->normalizeUnitName($label);
        $abbreviation = null;

        if (preg_match('/\(([^)]+)\)/u', $label, $matches)) {
            $abbreviation = trim($matches[1]);
        }

        return ItemUnit::firstOrCreate(
            ['name' => $name],
            ['abbreviation' => $abbreviation ?: mb_strtolower(mb_substr($name, 0, 3))]
        );
    }

    private function buildTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        $categories = ItemCategory::query()->orderBy('name')->pluck('name')->all();
        $units = ItemUnit::query()->orderBy('name')->get()
            ->map(fn (ItemUnit $u) => $this->unitLabel($u))->all();

        // 1. Sheet Utama: 'Import Barang' dijadikan sheet pertama aktif
        $importSheet = $spreadsheet->getActiveSheet();
        $importSheet->setTitle(self::IMPORT_SHEET);
        $importSheet->fromArray(self::IMPORT_HEADERS, null, 'A1');
        $this->styleHeaderRow($importSheet, 'A1:H1');

        $categoryLast = max(2, count($categories) + 1);
        $unitLast = max(2, count($units) + 1);

        $categoryFormula = "'".self::REF_CATEGORY_SHEET."'!\$A\$2:\$A\${$categoryLast}";
        $unitFormula = "'".self::REF_UNIT_SHEET."'!\$A\$2:\$A\${$unitLast}";
        $activeFormula = '"Ya,Tidak"';

        if (! empty($categories)) {
            $this->applyListValidation($importSheet, 'B', 2, self::IMPORT_MAX_ROWS + 1, $categoryFormula);
        }
        if (! empty($units)) {
            $this->applyListValidation($importSheet, 'C', 2, self::IMPORT_MAX_ROWS + 1, $unitFormula);
        }
        $this->applyListValidation($importSheet, 'H', 2, self::IMPORT_MAX_ROWS + 1, $activeFormula);

        $this->autoSizeColumns($importSheet, 'A', 'H');

        // 2. Sheet Panduan
        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle(self::GUIDE_SHEET);
        $this->buildGuideSheet($guideSheet);

        // 3. Sheet Referensi Kategori
        $categorySheet = $spreadsheet->createSheet();
        $categorySheet->setTitle(self::REF_CATEGORY_SHEET);
        $categorySheet->setCellValue('A1', 'Kategori');
        $this->fillReferenceColumn($categorySheet, $categories);

        // 4. Sheet Referensi Satuan
        $unitSheet = $spreadsheet->createSheet();
        $unitSheet->setTitle(self::REF_UNIT_SHEET);
        $unitSheet->setCellValue('A1', 'Satuan');
        $this->fillReferenceColumn($unitSheet, $units);

        $categorySheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
        $unitSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function buildGuideSheet(Worksheet $sheet): void
    {
        $lines = [
            ['PANDUAN IMPORT DATA BARANG'],
            [''],
            ['1. Buka sheet "Import Barang" untuk mengisi data.'],
            ['2. Kolom Kategori & Satuan memiliki dropdown — pilihan diisi otomatis dari database.'],
            ['3. Kode barang dibuat otomatis oleh sistem saat import (atau gunakan kode lama jika mengupdate).'],
            ['4. Stok Awal: isi jumlah stok yang tersedia saat ini (default 0 jika kosong).'],
            ['5. Isi data mulai baris 2 ke bawah pada sheet Import Barang.'],
            ['6. Kolom wajib: Nama Barang.'],
            ['7. Harga Beli dan Harga Jual boleh dikosongkan (default 0).'],
            ['8. Aktif: isi "Ya" atau "Tidak" (default Ya jika dikosongkan).'],
            [''],
            ['Sheet referensi kategori & satuan tersembunyi — jangan dihapus dari file Excel.'],
        ];

        $sheet->fromArray($lines, null, 'A1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getColumnDimension('A')->setWidth(80);
    }

    /**
     * @param  array<int, string>  $values
     */
    private function fillReferenceColumn(Worksheet $sheet, array $values): void
    {
        $row = 2;
        foreach ($values as $value) {
            $sheet->setCellValue("A{$row}", $value);
            $row++;
        }

        if (empty($values)) {
            $sheet->setCellValue('A2', '(Belum ada data — tambahkan di menu master)');
        }

        $this->styleHeaderRow($sheet, 'A1');
    }

    private function applyListValidation(
        Worksheet $sheet,
        string $column,
        int $startRow,
        int $endRow,
        string $formula,
    ): void {
        for ($row = $startRow; $row <= $endRow; $row++) {
            $cell = $sheet->getCell("{$column}{$row}");
            $validation = $cell->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setErrorTitle('Nilai tidak valid');
            $validation->setError('Pilih salah satu opsi dari daftar.');
            $validation->setFormula1($formula);
        }
    }

    private function styleHeaderRow(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1A1A1A']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D4A017'],
            ],
        ]);
    }

    private function autoSizeColumns(Worksheet $sheet, string $from, string $to): void
    {
        foreach (range($from, $to) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function streamSpreadsheet(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * @return Collection<string, ItemUnit>
     */
    private function buildUnitLookup(Collection $units): Collection
    {
        $lookup = collect();

        foreach ($units as $unit) {
            $lookup->put(mb_strtolower($this->unitLabel($unit)), $unit);
            $lookup->put(mb_strtolower($unit->name), $unit);

            if ($unit->abbreviation) {
                $lookup->put(mb_strtolower($unit->abbreviation), $unit);
            }
        }

        return $lookup;
    }

    private function unitLabel(?ItemUnit $unit): string
    {
        if (! $unit) {
            return '';
        }

        return $unit->abbreviation
            ? "{$unit->name} ({$unit->abbreviation})"
            : $unit->name;
    }

    private function normalizeUnitName(string $label): string
    {
        if (preg_match('/^(.+?)\s*\([^)]+\)\s*$/u', $label, $matches)) {
            return trim($matches[1]);
        }

        return $label;
    }

    private function parseYesNo(mixed $value, bool $default = true): bool
    {
        $text = mb_strtolower(trim((string) $value));

        if ($text === '') {
            return $default;
        }

        return in_array($text, ['ya', 'y', 'yes', '1', 'true', 'aktif'], true);
    }

    private function parseNumber(mixed $value, float $default = 0): float
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $str = trim((string) $value);
        // Hapus karakter non-angka kecuali titik, koma, minus
        $str = preg_replace('/[^\d.,\-]/i', '', $str);

        if ($str === '') {
            return $default;
        }

        if (str_contains($str, '.') && str_contains($str, ',')) {
            $lastDot = strrpos($str, '.');
            $lastComma = strrpos($str, ',');
            if ($lastDot > $lastComma) {
                $str = str_replace(',', '', $str);
            } else {
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            }
        } elseif (str_contains($str, '.')) {
            if (substr_count($str, '.') > 1 || preg_match('/\.\d{3}$/', $str)) {
                $str = str_replace('.', '', $str);
            }
        } elseif (str_contains($str, ',')) {
            if (substr_count($str, ',') > 1 || preg_match('/,\d{3}$/', $str)) {
                $str = str_replace(',', '', $str);
            } else {
                $str = str_replace(',', '.', $str);
            }
        }

        return is_numeric($str) ? (float) $str : $default;
    }

    private function parseInt(mixed $value, int $default = 0): int
    {
        return (int) round($this->parseNumber($value, (float) $default));
    }
}
