<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan Kategori - {{ brand_name() }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #2d3748; line-height: 1.4; margin: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #008a50; padding-bottom: 10px; }
        .header h1 { font-size: 18px; margin: 0; color: #008a50; text-transform: uppercase; }
        .header p { margin: 2px 0; color: #718096; font-size: 10px; }
        .period { margin-top: 5px; font-weight: bold; font-size: 11px; color: #1a202c; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data th, table.data td { border: 1px solid #cbd5e0; padding: 6px 8px; }
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
            LAPORAN PENJUALAN PER KATEGORI — PERIODE {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} S/D {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
        </div>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th>Nama Kategori</th>
                <th class="text-center">Variasi SKU</th>
                <th class="text-center">Qty Terjual</th>
                <th class="text-end">Total Omset</th>
                <th class="text-end">Total Modal</th>
                <th class="text-end">Laba Kotor</th>
                <th class="text-end">Margin</th>
                <th class="text-end">Kontribusi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($categories as $idx => $cat)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td style="font-weight: bold;">{{ $cat['category_name'] }}</td>
                    <td class="text-center">{{ $cat['items_count'] }}</td>
                    <td class="text-center">{{ $cat['total_qty'] }}</td>
                    <td class="text-end" style="font-weight: bold;">{{ $rp($cat['total_sales']) }}</td>
                    <td class="text-end">{{ $rp($cat['total_cost']) }}</td>
                    <td class="text-end">{{ $rp($cat['gross_profit']) }}</td>
                    <td class="text-end">{{ $cat['margin_percent'] }}%</td>
                    <td class="text-end">{{ $cat['share_percent'] }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data penjualan kategori pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($categories->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end">TOTAL KESELURUHAN:</td>
                    <td class="text-center">{{ number_format($summary['total_qty'], 0, ',', '.') }}</td>
                    <td class="text-end">{{ $rp($summary['total_sales']) }}</td>
                    <td class="text-end">{{ $rp($summary['total_cost']) }}</td>
                    <td class="text-end">{{ $rp($summary['gross_profit']) }}</td>
                    <td class="text-end">{{ $summary['avg_margin'] }}%</td>
                    <td class="text-end">100%</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        Dicetak otomatis oleh Sistem {{ brand_name() }} pada {{ now()->format('d/m/Y H:i') }} WIB
    </div>
</body>
</html>
