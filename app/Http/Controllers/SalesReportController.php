<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SalesReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SalesReportController extends Controller
{
    public function __construct(private SalesReportService $reportService)
    {
        $this->middleware('permission:sales report view');
    }

    public function index(Request $request): View
    {
        [$from, $to] = $this->resolvePeriod($request);
        $filters = [
            'payment_method' => $request->query('payment_method'),
            'user_id' => $request->query('user_id'),
            'search' => $request->query('search'),
        ];

        $summaryCards = $this->reportService->getSummaryCards();
        $report = $this->reportService->getSalesReport($from, $to, $filters);
        $cashiers = User::query()->orderBy('name')->get(['id', 'name']);

        return view('sales-reports.index', [
            'summaryCards' => $summaryCards,
            'report' => $report,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'filters' => $filters,
            'cashiers' => $cashiers,
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        [$from, $to] = $this->resolvePeriod($request);
        $filters = [
            'payment_method' => $request->query('payment_method'),
            'user_id' => $request->query('user_id'),
            'search' => $request->query('search'),
        ];

        $summaryCards = $this->reportService->getSummaryCards();
        $report = $this->reportService->getSalesReport($from, $to, $filters);

        $pdf = Pdf::loadView('sales-reports.pdf', [
            'summaryCards' => $summaryCards,
            'report' => $report,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'filters' => $filters,
        ])->setPaper('a4', 'landscape');

        $filename = 'laporan-penjualan-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf';

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
