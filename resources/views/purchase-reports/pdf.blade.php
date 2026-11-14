<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pembelian - {{ brand_name() }}</title>
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
            LAPORAN PEMBELIAN BARANG — PERIODE {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} S/D {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
        </div>
    </div>

    {{-- 4 KPI UTAMA --}}
    <table class="kpi-row">
        <tr>
            <td class="kpi-box">
                <div class="kpi-title">Pembelian Hari Ini</div>
                <div class="kpi-val">{{ $rp($summaryCards['today']['total']) }}</div>
                <div style="font-size: 9px; color: #718096;">{{ $summaryCards['today']['count'] }} Transaksi</div>
            </td>
            <td class="kpi-box">
                <div class="kpi-title">Pembelian Bulan Ini</div>
                <div class="kpi-val">{{ $rp($summaryCards['month']['total']) }}</div>
                <div style="font-size: 9px; color: #718096;">{{ $summaryCards['month']['count'] }} Transaksi</div>
            </td>
            <td class="kpi-box">
                <div class="kpi-title">Pembelian Tahun Ini</div>
                <div class="kpi-val">{{ $rp($summaryCards['year']['total']) }}</div>
                <div style="font-size: 9px; color: #718096;">{{ $summaryCards['year']['count'] }} Transaksi</div>
            </td>
            <td class="kpi-box">
                <div class="kpi-title">Pembelian Selama Ini</div>
                <div class="kpi-val">{{ $rp($summaryCards['all_time']['total']) }}</div>
                <div style="font-size: 9px; color: #718096;">{{ $summaryCards['all_time']['count'] }} Transaksi</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th>No. Pembelian</th>
                <th>Tanggal & Waktu</th>
                <th>Supplier</th>
                <th>PIC / User</th>
                <th>Metode Bayar</th>
                <th class="text-center">Status</th>
                <th class="text-end">Total Pembelian</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['purchases'] as $idx => $p)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $p->purchase_no }}</td>
                    <td>{{ $p->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $p->displaySupplierName() }}</td>
                    <td>{{ $p->user?->name ?? '-' }}</td>
                    <td>{{ $p->payment_method === 'cash' ? 'Tunai' : ($p->payment_method === 'transfer' ? 'Transfer (' . ($p->bankAccount?->bank_name ?? 'Bank') . ')' : ucfirst($p->payment_method)) }}</td>
                    <td class="text-center">{{ $p->status === 'completed' ? 'Selesai' : 'Batal' }}</td>
                    <td class="text-end" style="font-weight: bold;">{{ $rp($p->total) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px; color: #718096;">
                        Tidak ada data pembelian untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($report['count'] > 0)
            <tfoot>
                <tr>
                    <td colspan="7" class="text-end">TOTAL PEMBELIAN:</td>
                    <td class="text-end" style="color: #008a50; font-size: 12px;">{{ $rp($report['total_purchases']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        Dicetak otomatis oleh sistem {{ brand_name() }} pada {{ now()->translatedFormat('d F Y H:i') }} WIB
    </div>
</body>
</html>
