<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToModal;
use App\Services\ItemExcelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemImportExportController extends Controller
{
    use RespondsToModal;

    public function __construct(private ItemExcelService $excelService)
    {
        $this->middleware('permission:item export')->only('export');
        $this->middleware('permission:item export|item import')->only('template');
        $this->middleware('permission:item import')->only('import');
    }

    public function export(): StreamedResponse
    {
        return $this->excelService->export();
    }

    public function template(): StreamedResponse
    {
        return $this->excelService->downloadTemplate();
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['xlsx', 'xls', 'csv'])) {
            return $this->modalError('Format file tidak didukung. Harap upload file Excel (.xlsx, .xls) atau .csv.');
        }

        try {
            $result = $this->excelService->import($file);
        } catch (\Throwable $e) {
            report($e);

            return $this->modalError('Gagal membaca file Excel: ' . $e->getMessage());
        }

        $message = "Import selesai: {$result['created']} barang berhasil diproses";
        if ($result['skipped'] > 0) {
            $message .= ", {$result['skipped']} baris dilewati";
        }
        $message .= '.';

        return response()->json([
            'message' => $message,
            'data' => $result,
        ]);
    }
}
