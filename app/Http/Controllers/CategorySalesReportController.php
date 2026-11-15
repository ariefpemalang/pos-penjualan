<?php

namespace App\Http\Controllers;

use App\Services\SalesReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CategorySalesReportController extends Controller
{
    public function __construct(private SalesReportService $reportService)
    {
        $this->middleware('permission:category sales report view');
    }

    public function index(Request $request): View
    {
        [$from, $to] = $this->resolvePeriod($request);

        $data = $this->reportService->getCategorySalesReport($from, $to);

        return view('category-sales-reports.index', [
            'summary' => $data['summary'],
            'categories' => $data['categories'],
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        [$from, $to] = $this->resolvePeriod($request);

        $data = $this->reportService->getCategorySalesReport($from, $to);

        $pdf = Pdf::loadView('category-sales-reports.pdf', [
            'summary' => $data['summary'],
            'categories' => $data['categories'],
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ])->setPaper('a4', 'portrait');

        $filename = 'laporan-penjualan-kategori-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString());
        $to = Carbon::parse($validated['to'] ?? now()->toDateString());

        if ($from->gt($to)) {
            $from = $to->copy()->startOfMonth();
        }

        return [$from, $to];
    }
}
