@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Keuangan', 'url' => route('financial-reports.index')],
            ['label' => 'Laporan Penjualan'],
        ],
        'title' => 'Laporan Penjualan',
        'subtitle' => 'Pantau ringkasan transaksi hari ini, bulan ini, tahun ini, dan penjualan selama ini.',
    ])

    @php
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    @endphp

    {{-- ══════════════════════════════════════════════════════════════
         4 KARTU KPI UTAMA (SESUAI REQUEST USER)
         ══════════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">
        {{-- 1. Transaksi Hari Ini --}}
        <div class="col-sm-6 col-xl-3">
            <div class="data-panel h-100 p-3 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Transaksi Hari Ini</span>
                    <span class="badge bg-success-subtle text-success p-2 rounded-3">
                        <i class="bi bi-calendar-check fs-6"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-success">{{ $rp($summaryCards['today']['total']) }}</div>
                <div class="small text-muted mt-1 d-flex justify-content-between">
                    <span><i class="bi bi-receipt me-1"></i><strong>{{ $summaryCards['today']['count'] }}</strong> Transaksi</span>
                    <span><strong>{{ $summaryCards['today']['items_sold'] }}</strong> Item</span>
                </div>
            </div>
        </div>

        {{-- 2. Penjualan Bulan Ini --}}
        <div class="col-sm-6 col-xl-3">
            <div class="data-panel h-100 p-3 border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Penjualan Bulan Ini</span>
                    <span class="badge bg-primary-subtle text-primary p-2 rounded-3">
                        <i class="bi bi-calendar-month fs-6"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-primary">{{ $rp($summaryCards['month']['total']) }}</div>
                <div class="small text-muted mt-1 d-flex justify-content-between">
                    <span><i class="bi bi-receipt me-1"></i><strong>{{ $summaryCards['month']['count'] }}</strong> Transaksi</span>
                    <span><strong>{{ $summaryCards['month']['items_sold'] }}</strong> Item</span>
                </div>
            </div>
        </div>

        {{-- 3. Penjualan Tahun Ini --}}
        <div class="col-sm-6 col-xl-3">
            <div class="data-panel h-100 p-3 border-start border-4 border-warning">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Penjualan Tahun Ini</span>
                    <span class="badge bg-warning-subtle text-warning p-2 rounded-3">
                        <i class="bi bi-calendar2-range fs-6"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-warning">{{ $rp($summaryCards['year']['total']) }}</div>
                <div class="small text-muted mt-1">
                    <span><i class="bi bi-receipt me-1"></i><strong>{{ $summaryCards['year']['count'] }}</strong> Transaksi selesai tahun {{ now()->year }}</span>
                </div>
            </div>
        </div>

        {{-- 4. Penjualan Selama Ini --}}
        <div class="col-sm-6 col-xl-3">
            <div class="data-panel h-100 p-3 border-start border-4 border-info">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Penjualan Selama Ini</span>
                    <span class="badge bg-info-subtle text-info p-2 rounded-3">
                        <i class="bi bi-graph-up-arrow fs-6"></i>
                    </span>
                </div>
                <div class="fs-4 fw-bold text-info">{{ $rp($summaryCards['all_time']['total']) }}</div>
                <div class="small text-muted mt-1">
                    <span><i class="bi bi-infinity me-1"></i>Total <strong>{{ $summaryCards['all_time']['count'] }}</strong> Transaksi akumulasi</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         FILTER & NAVIGASI LAPORAN
         ══════════════════════════════════════════════════════════════ --}}
    <div class="data-panel mb-4 no-print">
        <div class="data-panel-head">
            <div class="data-panel-icon"><i class="bi bi-funnel"></i></div>
            <div class="flex-grow-1">
                <h3 class="data-panel-title">Filter Periode & Penjualan</h3>
                <p class="data-panel-desc">
                    Menampilkan data periode:
                    <strong>{{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}</strong>
                    s/d
                    <strong>{{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</strong>
                    ({{ $report['summary']['count'] }} Transaksi, Total {{ $rp($report['summary']['total_sales']) }})
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('item-sales-reports.index', ['from' => $from, 'to' => $to]) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-box-seam me-1"></i> Laporan Barang
                </a>
                <a href="{{ route('category-sales-reports.index', ['from' => $from, 'to' => $to]) }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-tags me-1"></i> Laporan Kategori
                </a>
                <a href="{{ route('sales-reports.export-pdf', request()->query()) }}" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Cetak
                </button>
            </div>
        </div>
        <div class="data-panel-body pt-3">
            <form method="get" action="{{ route('sales-reports.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control form-control-clean" value="{{ $from }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control form-control-clean" value="{{ $to }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Metode Bayar</label>
                    <select name="payment_method" class="form-select form-control-clean">
                        <option value="">Semua Metode</option>
                        <option value="cash" @selected(($filters['payment_method'] ?? '') === 'cash')>Tunai (Cash)</option>
                        <option value="transfer" @selected(($filters['payment_method'] ?? '') === 'transfer')>Transfer</option>
                        <option value="qris" @selected(($filters['payment_method'] ?? '') === 'qris')>QRIS</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Kasir</label>
                    <select name="user_id" class="form-select form-control-clean">
                        <option value="">Semua Kasir</option>
                        @foreach ($cashiers as $c)
                            <option value="{{ $c->id }}" @selected(($filters['user_id'] ?? '') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                    @if (request()->hasAny(['from', 'to', 'payment_method', 'user_id', 'search']))
                        <a href="{{ route('sales-reports.index') }}" class="btn btn-outline-secondary" title="Reset">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         RINGKASAN PERIODE TERPILIH & METODE BAYAR
         ══════════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="data-panel h-100 p-3">
                <h4 class="data-panel-title mb-3"><i class="bi bi-pie-chart me-2"></i>Ringkasan Penjualan Periode Terpilih</h4>
                <div class="row g-3 text-center">
                    <div class="col-sm-3 col-6">
                        <div class="p-2 border rounded-3 bg-light-subtle">
                            <span class="text-muted small d-block mb-1">Total Transaksi</span>
                            <span class="fw-bold fs-5">{{ $report['summary']['count'] }}</span>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="p-2 border rounded-3 bg-light-subtle">
                            <span class="text-muted small d-block mb-1">Total Omset</span>
                            <span class="fw-bold fs-5 text-success">{{ $rp($report['summary']['total_sales']) }}</span>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="p-2 border rounded-3 bg-light-subtle">
                            <span class="text-muted small d-block mb-1">Total Modal (HPP)</span>
                            <span class="fw-bold fs-5 text-muted">{{ $rp($report['summary']['total_cost']) }}</span>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="p-2 border rounded-3 bg-light-subtle">
                            <span class="text-muted small d-block mb-1">Estimasi Laba Kotor</span>
                            <span class="fw-bold fs-5 text-primary">{{ $rp($report['summary']['gross_profit']) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="data-panel h-100 p-3">
                <h4 class="data-panel-title mb-3"><i class="bi bi-wallet2 me-2"></i>Metode Pembayaran</h4>
                @if ($report['payment_methods']->isEmpty())
                    <div class="text-muted small py-3 text-center">Belum ada transaksi di periode ini.</div>
                @else
                    <div class="d-flex flex-column gap-2">
                        @foreach ($report['payment_methods'] as $pm)
                            <div>
                                <div class="d-flex justify-content-between small fw-semibold mb-1">
                                    <span>{{ $pm['label'] }} ({{ $pm['count'] }})</span>
                                    <span>{{ $rp($pm['total']) }} <span class="text-muted">({{ $pm['percentage'] }}%)</span></span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: {{ $pm['percentage'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         TABEL DAFTAR TRANSAKSI PENJUALAN
         ══════════════════════════════════════════════════════════════ --}}
    <div class="data-panel">
        <div class="data-panel-head">
            <div class="data-panel-icon"><i class="bi bi-table"></i></div>
            <div class="flex-grow-1">
                <h3 class="data-panel-title">Daftar Transaksi Penjualan</h3>
                <p class="data-panel-desc">Menampilkan rincian transaksi penjualan yang telah diselesaikan (completed).</p>
            </div>
        </div>
        <div class="data-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 40px;">No</th>
                            <th>No. Transaksi</th>
                            <th>Tanggal & Waktu</th>
                            <th>Kasir</th>
                            <th>Item Terjual</th>
                            <th>Metode Bayar</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Diskon</th>
                            <th class="text-end pe-3">Total</th>
                            <th class="text-center no-print" style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['transactions'] as $idx => $t)
                            <tr>
                                <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                                <td>
                                    <span class="fw-bold text-primary">{{ $t->transaction_no }}</span>
                                </td>
                                <td>
                                    <div>{{ $t->created_at->format('d/m/Y') }}</div>
                                    <div class="small text-muted">{{ $t->created_at->format('H:i') }} WIB</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $t->user?->name ?? 'Kasir' }}</span>
                                </td>
                                <td>
                                    <div class="small">
                                        @foreach ($t->items->take(2) as $item)
                                            <div>{{ $item->item_name }} <span class="text-muted">x{{ $item->quantity }}</span></div>
                                        @endforeach
                                        @if ($t->items->count() > 2)
                                            <span class="text-muted">+{{ $t->items->count() - 2 }} item lainnya</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success">
                                        {{ $t->displayPaymentLabel() }}
                                    </span>
                                </td>
                                <td class="text-end">{{ $rp($t->subtotal_items + $t->subtotal_services) }}</td>
                                <td class="text-end text-danger">{{ $t->discount > 0 ? '-' . $rp($t->discount) : '-' }}</td>
                                <td class="text-end pe-3 fw-bold text-success">{{ $rp($t->total) }}</td>
                                <td class="text-center no-print">
                                    <a href="{{ route('transactions.invoice', $t) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Cetak Nota">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    Tidak ada data transaksi penjualan pada periode yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($report['transactions']->isNotEmpty())
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="6" class="text-end ps-3">TOTAL KESELURUHAN:</td>
                                <td class="text-end">{{ $rp($report['summary']['gross']) }}</td>
                                <td class="text-end text-danger">{{ $report['summary']['discount'] > 0 ? '-' . $rp($report['summary']['discount']) : '-' }}</td>
                                <td class="text-end pe-3 text-success fs-6">{{ $rp($report['summary']['total_sales']) }}</td>
                                <td class="no-print"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
