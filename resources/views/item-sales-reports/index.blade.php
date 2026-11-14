@extends('layouts.app')

@section('title', 'Laporan Penjualan Barang')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Keuangan', 'url' => route('financial-reports.index')],
            ['label' => 'Laporan Penjualan Barang'],
        ],
        'title' => 'Laporan Penjualan Barang',
        'subtitle' => 'Pantau performa penjualan tiap barang, kuantitas terjual, total omset, modal, dan laba kotor.',
    ])

    @php
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    @endphp

    {{-- ══════════════════════════════════════════════════════════════
         KARTU KPI UTAMA PENJUALAN BARANG
         ══════════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="data-panel h-100 p-3 border-start border-4 border-info">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Ragam Produk Terjual</span>
                    <span class="badge bg-info-subtle text-info p-2 rounded-3">
                        <i class="bi bi-box-seam fs-6"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-info">{{ $summary['unique_items'] }} SKU</div>
                <div class="small text-muted mt-1">Variasi item berbeda terjual</div>
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
                <div class="small text-muted mt-1">Kuantitas fisik produk keluar</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="data-panel h-100 p-3 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Total Omset Penjualan</span>
                    <span class="badge bg-success-subtle text-success p-2 rounded-3">
                        <i class="bi bi-cash-stack fs-6"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-success">{{ $rp($summary['total_sales']) }}</div>
                <div class="small text-muted mt-1">Total nilai penjualan barang</div>
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
                <h3 class="data-panel-title">Filter Penjualan Barang</h3>
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
                <a href="{{ route('category-sales-reports.index', ['from' => $from, 'to' => $to]) }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-tags me-1"></i> Laporan Kategori
                </a>
                <a href="{{ route('item-sales-reports.export-pdf', request()->query()) }}" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Cetak
                </button>
            </div>
        </div>
        <div class="data-panel-body pt-3">
            <form method="get" action="{{ route('item-sales-reports.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control form-control-clean" value="{{ $from }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control form-control-clean" value="{{ $to }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Kategori Barang</label>
                    <select name="category_id" class="form-select form-control-clean">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected($categoryId == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <div class="flex-grow-1">
                        <label class="form-label small fw-semibold mb-1">Cari Barang</label>
                        <input type="text" name="search" class="form-control form-control-clean" placeholder="Nama / Kode..." value="{{ $search }}">
                    </div>
                    <div>
                        <label class="form-label d-block small mb-1">&nbsp;</label>
                        <button type="submit" class="btn btn-primary" title="Cari / Filter">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    @if (request()->hasAny(['from', 'to', 'category_id', 'search']))
                        <div>
                            <label class="form-label d-block small mb-1">&nbsp;</label>
                            <a href="{{ route('item-sales-reports.index') }}" class="btn btn-outline-secondary" title="Reset">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         TABEL PENJUALAN BARANG
         ══════════════════════════════════════════════════════════════ --}}
    <div class="data-panel">
        <div class="data-panel-head">
            <div class="data-panel-icon"><i class="bi bi-table"></i></div>
            <div class="flex-grow-1">
                <h3 class="data-panel-title">Tabel Rincian Penjualan Barang</h3>
                <p class="data-panel-desc">Urutan barang dari penjualan tertinggi (Best Seller).</p>
            </div>
        </div>
        <div class="data-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 40px;">No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th class="text-end">Rata2 Harga</th>
                            <th class="text-end">Rata2 Modal</th>
                            <th class="text-center">Qty Terjual</th>
                            <th class="text-end">Total Omset</th>
                            <th class="text-end">Total Modal</th>
                            <th class="text-end">Laba Kotor</th>
                            <th class="text-end pe-3">Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $idx => $item)
                            <tr>
                                <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $item['item_code'] }}</span>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $item['item_name'] }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $item['category_name'] }}</span>
                                </td>
                                <td class="text-end">{{ $rp($item['avg_price']) }}</td>
                                <td class="text-end text-muted">{{ $rp($item['avg_cost']) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1">
                                        {{ $item['total_qty'] }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-success">{{ $rp($item['total_sales']) }}</td>
                                <td class="text-end text-muted">{{ $rp($item['total_cost']) }}</td>
                                <td class="text-end fw-bold {{ $item['gross_profit'] >= 0 ? 'text-primary' : 'text-danger' }}">
                                    {{ $rp($item['gross_profit']) }}
                                </td>
                                <td class="text-end pe-3">
                                    <span class="badge {{ $item['margin_percent'] >= 20 ? 'bg-success-subtle text-success' : ($item['margin_percent'] >= 10 ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger') }}">
                                        {{ $item['margin_percent'] }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    Tidak ada data barang terjual pada periode / filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($items->isNotEmpty())
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="6" class="text-end ps-3">TOTAL:</td>
                                <td class="text-center">{{ number_format($summary['total_qty'], 0, ',', '.') }} Unit</td>
                                <td class="text-end text-success">{{ $rp($summary['total_sales']) }}</td>
                                <td class="text-end text-muted">{{ $rp($summary['total_cost']) }}</td>
                                <td class="text-end text-primary">{{ $rp($summary['gross_profit']) }}</td>
                                <td class="text-end pe-3">{{ $summary['avg_margin'] }}%</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
