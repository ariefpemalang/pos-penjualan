<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan - {{ brand_name() }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #2d3748; line-height: 1.4; margin: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #008a50; padding-bottom: 10px; }
        .header h1 { font-size: 18px; margin: 0; color: #008a50; text-transform: uppercase; }
        .header p { margin: 2px 0; color: #718096; font-size: 11px; }
        .period { margin-top: 5px; font-weight: bold; font-size: 12px; color: #1a202c; }
        .kpi-row { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .kpi-box { width: 25%; padding: 8px; border: 1px solid #e2e8f0; background-color: #f7fafc; text-align: center; }
        .kpi-title { font-size: 9px; text-transform: uppercase; color: #718096; margin-bottom: 4px; }
        .kpi-val { font-size: 13px; font-weight: bold; color: #008a50; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data th, table.data td { border: 1px solid #cbd5e0; padding: 6px 8px; }
        table.data th { background-color: #edf2f7; font-size: 10px; text-transform: uppercase; }
        table.data tfoot td { font-weight: bold; background-color: #edf2f7; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 25px; text-align: right; font-size: 9px; color: #a0aec0; }
    </style>
</head>
<body>
    @php
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    @endphp

    <div class="header">
        <h1>{{ brand_name() }}</h1>
        <p>{{ brand_tagline() }}</p>
        <p>{{ brand_address() }}</p>
        <div class="period">
            LAPORAN PENJUALAN — PERIODE {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} S/D {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
        </div>
    </div>

    {{-- 4 KPI UTAMA --}}
    <table class="kpi-row">
        <tr>
            <td class="kpi-box">
                <div class="kpi-title">Transaksi Hari Ini</div>
                <div class="kpi-val">{{ $rp($summaryCards['today']['total']) }}</div>
                <div style="font-size: 9px; color: #718096;">{{ $summaryCards['today']['count'] }} Transaksi</div>
            </td>
            <td class="kpi-box">
                <div class="kpi-title">Penjualan Bulan Ini</div>
                <div class="kpi-val">{{ $rp($summaryCards['month']['total']) }}</div>
                <div style="font-size: 9px; color: #718096;">{{ $summaryCards['month']['count'] }} Transaksi</div>
            </td>
            <td class="kpi-box">
                <div class="kpi-title">Penjualan Tahun Ini</div>
                <div class="kpi-val">{{ $rp($summaryCards['year']['total']) }}</div>
                <div style="font-size: 9px; color: #718096;">{{ $summaryCards['year']['count'] }} Transaksi</div>
            </td>
            <td class="kpi-box">
                <div class="kpi-title">Penjualan Selama Ini</div>
                <div class="kpi-val">{{ $rp($summaryCards['all_time']['total']) }}</div>
                <div style="font-size: 9px; color: #718096;">{{ $summaryCards['all_time']['count'] }} Transaksi</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th>No. Transaksi</th>
                <th>Tanggal & Waktu</th>
                <th>Kasir</th>
                <th>Metode Bayar</th>
                <th class="text-end">Subtotal</th>
                <th class="text-end">Diskon</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['transactions'] as $idx => $t)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $t->transaction_no }}</td>
                    <td>{{ $t->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $t->user?->name ?? 'Kasir' }}</td>
                    <td>{{ $t->displayPaymentLabel() }}</td>
                    <td class="text-end">{{ $rp($t->subtotal_items + $t->subtotal_services) }}</td>
                    <td class="text-end">{{ $t->discount > 0 ? '-' . $rp($t->discount) : '-' }}</td>
                    <td class="text-end" style="font-weight: bold;">{{ $rp($t->total) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada transaksi penjualan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($report['transactions']->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="5" class="text-end">TOTAL KESELURUHAN:</td>
                    <td class="text-end">{{ $rp($report['summary']['gross']) }}</td>
                    <td class="text-end">{{ $report['summary']['discount'] > 0 ? '-' . $rp($report['summary']['discount']) : '-' }}</td>
                    <td class="text-end">{{ $rp($report['summary']['total_sales']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        Dicetak otomatis oleh Sistem {{ brand_name() }} pada {{ now()->format('d/m/Y H:i') }} WIB
    </div>
</body>
</html>
