@extends('layouts.app')

@section('title', 'Laporan Penjualan Kategori')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Keuangan', 'url' => route('financial-reports.index')],
            ['label' => 'Laporan Penjualan Kategori'],
        ],
        'title' => 'Laporan Penjualan Kategori',
        'subtitle' => 'Pantau kontribusi penjualan, total omset, modal HPP, dan margin keuntungan per kategori barang.',
    ])

    @php
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    @endphp

    {{-- ══════════════════════════════════════════════════════════════
         KARTU KPI UTAMA PENJUALAN KATEGORI
         ══════════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="data-panel h-100 p-3 border-start border-4 border-info">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Kategori Terjual</span>
                    <span class="badge bg-info-subtle text-info p-2 rounded-3">
                        <i class="bi bi-tags fs-6"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-info">{{ $summary['total_categories'] }} Kategori</div>
                <div class="small text-muted mt-1">Grup kategori yang memiliki transaksi</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="data-panel h-100 p-3 border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Total Unit Terjual</span>
                    <span class="badge bg-primary-subtle text-primary p-2 rounded-3">
                        <i class="bi bi-cart-check fs-6"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-primary">{{ number_format($summary['total_qty'], 0, ',', '.') }} Unit</div>
                <div class="small text-muted mt-1">Volume seluruh item keluar</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="data-panel h-100 p-3 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Total Omset Kategori</span>
                    <span class="badge bg-success-subtle text-success p-2 rounded-3">
                        <i class="bi bi-cash-stack fs-6"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-success">{{ $rp($summary['total_sales']) }}</div>
                <div class="small text-muted mt-1">Total pendapatan kotor penjualan</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="data-panel h-100 p-3 border-start border-4 border-warning">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Estimasi Laba Kotor</span>
                    <span class="badge bg-warning-subtle text-warning p-2 rounded-3">
                        <i class="bi bi-graph-up fs-6"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-warning">{{ $rp($summary['gross_profit']) }}</div>
                <div class="small text-muted mt-1">Rata-rata Margin: <strong>{{ $summary['avg_margin'] }}%</strong></div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         FILTER PANEL
         ══════════════════════════════════════════════════════════════ --}}
    <div class="data-panel mb-4 no-print">
        <div class="data-panel-head">
            <div class="data-panel-icon"><i class="bi bi-funnel"></i></div>
            <div class="flex-grow-1">
                <h3 class="data-panel-title">Filter Periode Laporan Kategori</h3>
                <p class="data-panel-desc">
                    Periode:
                    <strong>{{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}</strong>
                    s/d
                    <strong>{{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</strong>
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('sales-reports.index', ['from' => $from, 'to' => $to]) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-receipt me-1"></i> Laporan Transaksi
                </a>
                <a href="{{ route('item-sales-reports.index', ['from' => $from, 'to' => $to]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-box-seam me-1"></i> Laporan Barang
                </a>
                <a href="{{ route('category-sales-reports.export-pdf', request()->query()) }}" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Cetak
                </button>
            </div>
        </div>
        <div class="data-panel-body pt-3">
            <form method="get" action="{{ route('category-sales-reports.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control form-control-clean" value="{{ $from }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control form-control-clean" value="{{ $to }}" required>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> Terapkan Filter
                    </button>
                    @if (request()->hasAny(['from', 'to']))
                        <a href="{{ route('category-sales-reports.index') }}" class="btn btn-outline-secondary" title="Reset">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         TABEL PENJUALAN KATEGORI
         ══════════════════════════════════════════════════════════════ --}}
    <div class="data-panel">
        <div class="data-panel-head">
            <div class="data-panel-icon"><i class="bi bi-table"></i></div>
            <div class="flex-grow-1">
                <h3 class="data-panel-title">Tabel Kontribusi Penjualan per Kategori</h3>
                <p class="data-panel-desc">Urutan kategori berdasarkan kontribusi omset penjualan terbesar.</p>
            </div>
        </div>
        <div class="data-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 40px;">No</th>
                            <th>Nama Kategori</th>
                            <th class="text-center">Variasi SKU</th>
                            <th class="text-center">Qty Terjual</th>
                            <th class="text-end">Total Omset</th>
                            <th class="text-end">Total Modal</th>
                            <th class="text-end">Laba Kotor</th>
                            <th class="text-end">Margin</th>
                            <th class="text-end pe-3" style="width: 180px;">Kontribusi Omset</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $idx => $cat)
                            <tr>
                                <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                                <td>
                                    <div class="fw-bold text-primary">{{ $cat['category_name'] }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">{{ $cat['items_count'] }} SKU</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1">
                                        {{ $cat['total_qty'] }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-success">{{ $rp($cat['total_sales']) }}</td>
                                <td class="text-end text-muted">{{ $rp($cat['total_cost']) }}</td>
                                <td class="text-end fw-bold {{ $cat['gross_profit'] >= 0 ? 'text-primary' : 'text-danger' }}">
                                    {{ $rp($cat['gross_profit']) }}
                                </td>
                                <td class="text-end">
                                    <span class="badge {{ $cat['margin_percent'] >= 20 ? 'bg-success-subtle text-success' : ($cat['margin_percent'] >= 10 ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger') }}">
                                        {{ $cat['margin_percent'] }}%
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar bg-success" style="width: {{ $cat['share_percent'] }}%"></div>
                                        </div>
                                        <span class="small fw-bold" style="min-width: 45px;">{{ $cat['share_percent'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    Tidak ada data penjualan kategori pada periode yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($categories->isNotEmpty())
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="3" class="text-end ps-3">TOTAL KESELURUHAN:</td>
                                <td class="text-center">{{ number_format($summary['total_qty'], 0, ',', '.') }} Unit</td>
                                <td class="text-end text-success">{{ $rp($summary['total_sales']) }}</td>
                                <td class="text-end text-muted">{{ $rp($summary['total_cost']) }}</td>
                                <td class="text-end text-primary">{{ $rp($summary['gross_profit']) }}</td>
                                <td class="text-end">{{ $summary['avg_margin'] }}%</td>
                                <td class="text-end pe-3">100%</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
