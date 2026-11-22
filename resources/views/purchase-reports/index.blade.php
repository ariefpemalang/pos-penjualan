@extends('layouts.app')

@section('title', 'Laporan Pembelian')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Keuangan', 'url' => route('financial-reports.index')],
            ['label' => 'Laporan Pembelian'],
        ],
        'title' => 'Laporan Pembelian',
        'subtitle' => 'Pantau rekapitulasi data pembelian barang operasional hari ini, bulan ini, dan sepanjang waktu.',
    ])

    @php
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    @endphp

    {{-- ══════════════════════════════════════════════════════════════
         4 KARTU KPI UTAMA PEMBELIAN (STYLE DASHBOARD ATHA-ADMIN)
         ══════════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">
        {{-- 1. Pembelian Hari Ini --}}
        <div class="col-sm-6 col-xl-3">
            <div class="dash-kpi dash-kpi-primary h-100">
                <div class="dash-kpi-icon"><i class="bi bi-calendar-check"></i></div>
                <div class="dash-kpi-body">
                    <div class="dash-kpi-label">Pembelian Hari Ini</div>
                    <div class="dash-kpi-value text-primary">{{ $rp($summaryCards['today']['total']) }}</div>
                    <div class="dash-kpi-meta d-flex justify-content-between">
                        <span><i class="bi bi-cart-check me-1"></i><strong>{{ $summaryCards['today']['count'] }}</strong> Transaksi</span>
                        <span><strong>{{ $summaryCards['today']['items_bought'] }}</strong> Item</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Pembelian Bulan Ini --}}
        <div class="col-sm-6 col-xl-3">
            <div class="dash-kpi dash-kpi-success h-100">
                <div class="dash-kpi-icon"><i class="bi bi-calendar-month"></i></div>
                <div class="dash-kpi-body">
                    <div class="dash-kpi-label">Pembelian Bulan Ini</div>
                    <div class="dash-kpi-value text-success">{{ $rp($summaryCards['month']['total']) }}</div>
                    <div class="dash-kpi-meta d-flex justify-content-between">
                        <span><i class="bi bi-cart-check me-1"></i><strong>{{ $summaryCards['month']['count'] }}</strong> Transaksi</span>
                        <span><strong>{{ $summaryCards['month']['items_bought'] }}</strong> Item</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Pembelian Tahun Ini --}}
        <div class="col-sm-6 col-xl-3">
            <div class="dash-kpi dash-kpi-warning h-100">
                <div class="dash-kpi-icon"><i class="bi bi-calendar2-range"></i></div>
                <div class="dash-kpi-body">
                    <div class="dash-kpi-label">Pembelian Tahun Ini</div>
                    <div class="dash-kpi-value text-warning">{{ $rp($summaryCards['year']['total']) }}</div>
                    <div class="dash-kpi-meta">
                        <span><i class="bi bi-cart-check me-1"></i><strong>{{ $summaryCards['year']['count'] }}</strong> Transaksi selesai tahun {{ now()->year }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Pembelian Selama Ini --}}
        <div class="col-sm-6 col-xl-3">
            <div class="dash-kpi dash-kpi-info h-100">
                <div class="dash-kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="dash-kpi-body">
                    <div class="dash-kpi-label">Pembelian Selama Ini</div>
                    <div class="dash-kpi-value text-info">{{ $rp($summaryCards['all_time']['total']) }}</div>
                    <div class="dash-kpi-meta">
                        <span><i class="bi bi-infinity me-1"></i>Total <strong>{{ $summaryCards['all_time']['count'] }}</strong> Pembelian akumulasi</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         FILTER & PERIODE LAPORAN
         ══════════════════════════════════════════════════════════════ --}}
    <div class="data-panel mb-4 no-print">
        <div class="data-panel-head">
            <div class="data-panel-icon"><i class="bi bi-funnel"></i></div>
            <div class="flex-grow-1">
                <h3 class="data-panel-title">Filter Periode & Data Pembelian</h3>
                <p class="data-panel-desc">
                    Menampilkan data periode: <strong>{{ \Carbon\Carbon::parse($from)->translatedFormat('d F Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}</strong>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('purchase-reports.export-pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
                </a>
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-printer me-1"></i> Cetak
                </button>
            </div>
        </div>

        <div class="data-panel-body">
            <form method="GET" action="{{ route('purchase-reports.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Supplier</label>
                    <select name="supplier_id" class="form-select form-select-sm">
                        <option value="">Semua Supplier</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected($filters['supplier_id'] == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Metode Bayar</label>
                    <select name="payment_method" class="form-select form-select-sm">
                        <option value="">Semua Metode</option>
                        <option value="cash" @selected($filters['payment_method'] === 'cash')>Tunai</option>
                        <option value="transfer" @selected($filters['payment_method'] === 'transfer')>Transfer Bank</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="completed" @selected(($filters['status'] ?? 'completed') === 'completed')>Selesai</option>
                        <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Batal</option>
                        <option value="" @selected(($filters['status'] ?? '') === '')>Semua Status</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Pencarian</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Cari nomor invoice, supplier, atau catatan..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-6 d-flex gap-2 justify-content-md-end">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-filter me-1"></i> Terapkan Filter
                    </button>
                    <a href="{{ route('purchase-reports.index') }}" class="btn btn-outline-secondary btn-sm">
                        Reset
                    </a>
                </div>
            </form>

            {{-- Preset Cepat Periode --}}
            <div class="d-flex flex-wrap gap-1 mt-3 pt-2 border-top">
                <span class="text-muted small me-2 align-self-center">Pilihan Cepat:</span>
                <a href="{{ route('purchase-reports.index', array_merge(request()->query(), ['from' => now()->toDateString(), 'to' => now()->toDateString()])) }}"
                   class="btn btn-xs {{ $from === now()->toDateString() && $to === now()->toDateString() ? 'btn-primary' : 'btn-outline-secondary' }}">Hari Ini</a>
                <a href="{{ route('purchase-reports.index', array_merge(request()->query(), ['from' => now()->startOfWeek()->toDateString(), 'to' => now()->endOfWeek()->toDateString()])) }}"
                   class="btn btn-xs {{ $from === now()->startOfWeek()->toDateString() && $to === now()->endOfWeek()->toDateString() ? 'btn-primary' : 'btn-outline-secondary' }}">Minggu Ini</a>
                <a href="{{ route('purchase-reports.index', array_merge(request()->query(), ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()])) }}"
                   class="btn btn-xs {{ $from === now()->startOfMonth()->toDateString() && $to === now()->endOfMonth()->toDateString() ? 'btn-primary' : 'btn-outline-secondary' }}">Bulan Ini</a>
                <a href="{{ route('purchase-reports.index', array_merge(request()->query(), ['from' => now()->startOfYear()->toDateString(), 'to' => now()->endOfYear()->toDateString()])) }}"
                   class="btn btn-xs {{ $from === now()->startOfYear()->toDateString() && $to === now()->endOfYear()->toDateString() ? 'btn-primary' : 'btn-outline-secondary' }}">Tahun Ini</a>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         RINGKASAN METRIK PERIODE
         ══════════════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="p-3 rounded-3 text-center" style="background: var(--admin-surface-2); border: 1px solid var(--admin-border);">
                <div class="text-muted small text-uppercase fw-semibold">Total Nilai Pembelian</div>
                <div class="fs-4 fw-bold mt-1" style="color: var(--admin-text-heading);">{{ $rp($report['total_purchases']) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 rounded-3 text-center" style="background: var(--admin-surface-2); border: 1px solid var(--admin-border);">
                <div class="text-muted small text-uppercase fw-semibold">Jumlah Transaksi</div>
                <div class="fs-4 fw-bold mt-1" style="color: var(--admin-text-heading);">{{ $report['count'] }} <span class="fs-6 fw-normal text-muted">transaksi</span></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 rounded-3 text-center" style="background: var(--admin-surface-2); border: 1px solid var(--admin-border);">
                <div class="text-muted small text-uppercase fw-semibold">Total Item Barang Dibeli</div>
                <div class="fs-4 fw-bold mt-1" style="color: var(--admin-text-heading);">{{ $report['total_items_bought'] }} <span class="fs-6 fw-normal text-muted">unit</span></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 rounded-3 text-center" style="background: var(--admin-surface-2); border: 1px solid var(--admin-border);">
                <div class="text-muted small text-uppercase fw-semibold">Rata-rata per Transaksi</div>
                <div class="fs-4 fw-bold mt-1" style="color: var(--admin-text-heading);">{{ $rp($report['avg_ticket']) }}</div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         TABEL LAPORAN DATA PEMBELIAN (READ-ONLY)
         ══════════════════════════════════════════════════════════════ --}}
    <div class="data-panel">
        <div class="data-panel-head">
            <div class="data-panel-icon"><i class="bi bi-table"></i></div>
            <div class="flex-grow-1">
                <h3 class="data-panel-title">Rincian Pembelian Barang</h3>
                <p class="data-panel-desc">Rekapitulasi transaksi pembelian barang masuk (laporan keuangan read-only).</p>
            </div>
            <div class="badge bg-secondary-subtle text-secondary px-3 py-2">
                Total: {{ $report['count'] }} Record
            </div>
        </div>

        <div class="data-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="text-uppercase small">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>No. Pembelian</th>
                            <th>Tanggal & Waktu</th>
                            <th>Supplier</th>
                            <th>User / PIC</th>
                            <th>Metode Bayar</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Total Pembelian</th>
                            <th class="text-center" style="width: 100px;">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['purchases'] as $idx => $p)
                            <tr>
                                <td class="text-center text-muted small">{{ $idx + 1 }}</td>
                                <td>
                                    <span class="fw-semibold text-primary font-monospace">{{ $p->purchase_no }}</span>
                                </td>
                                <td>
                                    <div>{{ $p->created_at->format('d/m/Y') }}</div>
                                    <small class="text-muted">{{ $p->created_at->format('H:i') }} WIB</small>
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $p->displaySupplierName() }}</span>
                                </td>
                                <td>
                                    <span class="small">{{ $p->user?->name ?? '-' }}</span>
                                </td>
                                <td>
                                    @if ($p->payment_method === 'cash')
                                        <span class="badge bg-success-subtle text-success">Tunai</span>
                                    @elseif ($p->payment_method === 'transfer')
                                        <span class="badge bg-info-subtle text-info">Transfer ({{ $p->bankAccount?->bank_name ?? 'Bank' }})</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($p->payment_method) }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($p->status === 'completed')
                                        <span class="badge bg-success-subtle text-success">Selesai</span>
                                    @elseif ($p->status === 'cancelled')
                                        <span class="badge bg-danger-subtle text-danger">Batal</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($p->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold">
                                    {{ $rp($p->total) }}
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2"
                                            data-bs-toggle="modal" data-bs-target="#detailModal{{ $p->id }}" title="Lihat Item">
                                        <i class="bi bi-eye me-1"></i> Item
                                    </button>

                                    {{-- Modal Detail Items (Read-only) --}}
                                    <div class="modal fade" id="detailModal{{ $p->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered">
                                            <div class="modal-content text-start">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fs-6">
                                                        <i class="bi bi-receipt me-1"></i> Detail Pembelian #{{ $p->purchase_no }}
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row g-2 mb-3 pb-2 border-bottom small">
                                                        <div class="col-sm-6">
                                                            <strong>Supplier:</strong> {{ $p->displaySupplierName() }}<br>
                                                            <strong>Tanggal:</strong> {{ $p->created_at->format('d/m/Y H:i') }}
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <strong>Metode Bayar:</strong> {{ ucfirst($p->payment_method) }}<br>
                                                            <strong>PIC / Kasir:</strong> {{ $p->user?->name ?? '-' }}
                                                        </div>
                                                    </div>

                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered align-middle">
                                                            <thead class="text-muted small">
                                                                <tr>
                                                                    <th style="width: 40px;" class="text-center">No</th>
                                                                    <th>Kode</th>
                                                                    <th>Nama Barang</th>
                                                                    <th class="text-center" style="width: 80px;">Qty</th>
                                                                    <th class="text-end" style="width: 120px;">Harga Satuan</th>
                                                                    <th class="text-end" style="width: 130px;">Subtotal</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @forelse ($p->items as $itemIdx => $it)
                                                                    <tr>
                                                                        <td class="text-center">{{ $itemIdx + 1 }}</td>
                                                                        <td><code>{{ $it->item_code ?? '-' }}</code></td>
                                                                        <td>{{ $it->item_name }}</td>
                                                                        <td class="text-center">{{ $it->quantity }}</td>
                                                                        <td class="text-end">{{ $rp($it->unit_price) }}</td>
                                                                        <td class="text-end fw-semibold">{{ $rp($it->subtotal) }}</td>
                                                                    </tr>
                                                                @empty
                                                                    <tr>
                                                                        <td colspan="6" class="text-center text-muted py-2">Tidak ada data item.</td>
                                                                    </tr>
                                                                @endforelse
                                                            </tbody>
                                                            <tfoot style="background: var(--admin-surface-2);">
                                                                <tr>
                                                                    <td colspan="5" class="text-end fw-semibold">Subtotal</td>
                                                                    <td class="text-end fw-semibold">{{ $rp($p->subtotal) }}</td>
                                                                </tr>
                                                                @if ($p->discount > 0)
                                                                    <tr>
                                                                        <td colspan="5" class="text-end text-danger">Diskon</td>
                                                                        <td class="text-end text-danger">-{{ $rp($p->discount) }}</td>
                                                                    </tr>
                                                                @endif
                                                                <tr>
                                                                    <td colspan="5" class="text-end fw-bold">Total Pembelian</td>
                                                                    <td class="text-end fw-bold text-primary">{{ $rp($p->total) }}</td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>

                                                    @if ($p->notes)
                                                        <div class="p-2 rounded small mt-2" style="background: var(--admin-surface-2); border: 1px solid var(--admin-border);">
                                                            <strong>Catatan:</strong> {{ $p->notes }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer py-1">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    Tidak ada data pembelian pada periode yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($report['count'] > 0)
                        <tfoot class="fw-bold" style="background: var(--admin-surface-2);">
                            <tr>
                                <td colspan="7" class="text-end text-uppercase">Total Seluruh Pembelian:</td>
                                <td class="text-end text-primary fs-6">{{ $rp($report['total_purchases']) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
