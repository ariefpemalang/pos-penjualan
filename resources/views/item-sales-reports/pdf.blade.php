<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan Barang - {{ brand_name() }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #2d3748; line-height: 1.4; margin: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #008a50; padding-bottom: 10px; }
        .header h1 { font-size: 18px; margin: 0; color: #008a50; text-transform: uppercase; }
        .header p { margin: 2px 0; color: #718096; font-size: 10px; }
        .period { margin-top: 5px; font-weight: bold; font-size: 11px; color: #1a202c; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data th, table.data td { border: 1px solid #cbd5e0; padding: 5px 6px; }
        table.data th { background-color: #edf2f7; font-size: 9px; text-transform: uppercase; }
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
            LAPORAN PENJUALAN BARANG — PERIODE {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} S/D {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
        </div>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Rata2 Harga</th>
                <th class="text-end">Total Omset</th>
                <th class="text-end">Total Modal</th>
                <th class="text-end">Laba Kotor</th>
                <th class="text-end">Margin</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $item['item_code'] }}</td>
                    <td>{{ $item['item_name'] }}</td>
                    <td>{{ $item['category_name'] }}</td>
                    <td class="text-center">{{ $item['total_qty'] }}</td>
                    <td class="text-end">{{ $rp($item['avg_price']) }}</td>
                    <td class="text-end" style="font-weight: bold;">{{ $rp($item['total_sales']) }}</td>
                    <td class="text-end">{{ $rp($item['total_cost']) }}</td>
                    <td class="text-end">{{ $rp($item['gross_profit']) }}</td>
                    <td class="text-end">{{ $item['margin_percent'] }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">Tidak ada data penjualan barang pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($items->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end">TOTAL:</td>
                    <td class="text-center">{{ number_format($summary['total_qty'], 0, ',', '.') }}</td>
                    <td></td>
                    <td class="text-end">{{ $rp($summary['total_sales']) }}</td>
                    <td class="text-end">{{ $rp($summary['total_cost']) }}</td>
                    <td class="text-end">{{ $rp($summary['gross_profit']) }}</td>
                    <td class="text-end">{{ $summary['avg_margin'] }}%</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        Dicetak otomatis oleh Sistem {{ brand_name() }} pada {{ now()->format('d/m/Y H:i') }} WIB
    </div>
</body>
</html>
