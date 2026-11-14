@extends('layouts.app')

@section('title', 'Laporan Aset Kategori')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Laporan Aset Kategori'],
        ],
        'title' => 'Laporan Jumlah Uang Aset per Kategori',
        'subtitle' => 'Pantau jumlah uang aset stok barang, nilai jual, dan potensi margin berdasarkan grup kategori.',
    ])

    @php
        $summary = $report['summary'];
        $categories = $report['categories'];
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    @endphp

    {{-- ═══════════════════════════════════════════════════
         FILTER PANEL
         ═══════════════════════════════════════════════════ --}}
    <div class="data-panel mb-4 no-print">
        <div class="data-panel-head">
            <div class="data-panel-icon">
                <i class="bi bi-funnel"></i>
            </div>
            <div class="flex-grow-1">
                <h3 class="data-panel-title">Filter Grup Kategori</h3>
                <p class="data-panel-desc">
                    Kategori Terpilih: <strong>{{ $summary['selected_categories_count'] }}</strong> dari {{ $categories->count() }} kategori
                </p>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Cetak
            </button>
        </div>
        <div class="data-panel-body">
            <form method="get" action="{{ route('asset-reports.index') }}" id="filter-form">

                {{-- Pilihan Cepat Kategori --}}
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-uppercase mb-2" style="color:var(--admin-text-muted);letter-spacing:.04em;font-size:.72rem;">
                        <i class="bi bi-lightning-charge me-1"></i> Pilihan Cepat Kategori
                    </label>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm ar-quick-btn" id="btn-select-all">
                            <i class="bi bi-check-all me-1"></i> Semua Kategori
                        </button>
                        <button type="button" class="btn btn-sm ar-quick-btn" id="btn-unselect-all">
                            <i class="bi bi-x-circle me-1"></i> Kosongkan
                        </button>
                    </div>
                </div>

                {{-- Daftar Chip Kategori --}}
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach ($allCategories as $cat)
                        @php
                            $isChecked = in_array($cat->id, $selectedCategoryIds, true);
                        @endphp
                        <label class="ar-chip {{ $isChecked ? 'ar-chip-active' : '' }}">
                            <input type="checkbox" name="categories[]" value="{{ $cat->id }}"
                                class="category-checkbox d-none"
                                {{ $isChecked ? 'checked' : '' }}>
                            <i class="bi bi-check2 me-1 ar-chip-check"></i>
                            <span>{{ $cat->name }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="d-flex justify-content-end pt-2" style="border-top:1px solid var(--admin-border);">
                    <button type="submit" class="btn btn-sm px-4" style="background:var(--accent);color:#000;font-weight:600;">
                        <i class="bi bi-funnel me-1"></i> Terapkan Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         KPI SUMMARY CARDS
         ═══════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">
        {{-- Total Uang Aset --}}
        <div class="col-6 col-xl-3">
            <div class="dash-kpi dash-kpi-warning">
                <div class="dash-kpi-icon"><i class="bi bi-wallet2"></i></div>
                <div class="dash-kpi-body">
                    <div class="dash-kpi-label">TOTAL UANG ASET (HPP)</div>
                    <div class="dash-kpi-value text-warning">{{ $rp($summary['total_asset_cost']) }}</div>
                    <div class="dash-kpi-meta">{{ number_format($summary['total_items_count']) }} variasi produk</div>
                </div>
            </div>
        </div>

        {{-- Total Kuantitas Stok --}}
        <div class="col-6 col-xl-3">
            <div class="dash-kpi dash-kpi-primary">
                <div class="dash-kpi-icon"><i class="bi bi-box-seam"></i></div>
                <div class="dash-kpi-body">
                    <div class="dash-kpi-label">TOTAL KUANTITAS STOK</div>
                    <div class="dash-kpi-value text-primary">{{ number_format($summary['total_stock']) }} Unit</div>
                    <div class="dash-kpi-meta">{{ $summary['selected_categories_count'] }} kategori dipilih</div>
                </div>
            </div>
        </div>

        {{-- Estimasi Nilai Jual --}}
        <div class="col-6 col-xl-3">
            <div class="dash-kpi dash-kpi-info">
                <div class="dash-kpi-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="dash-kpi-body">
                    <div class="dash-kpi-label">ESTIMASI NILAI JUAL</div>
                    <div class="dash-kpi-value text-info">{{ $rp($summary['total_asset_sale']) }}</div>
                    <div class="dash-kpi-meta">Jika seluruh stok terjual</div>
                </div>
            </div>
        </div>

        {{-- Potensi Laba Kotor --}}
        <div class="col-6 col-xl-3">
            <div class="dash-kpi dash-kpi-success">
                <div class="dash-kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="dash-kpi-body">
                    <div class="dash-kpi-label">POTENSI LABA KOTOR</div>
                    <div class="dash-kpi-value text-success">+{{ $rp($summary['total_potential_margin']) }}</div>
                    <div class="dash-kpi-meta">Estimasi selisih margin</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         TABEL RINCIAN ASET PER KATEGORI
         ═══════════════════════════════════════════════════ --}}
    <div class="data-panel">
        <div class="data-panel-head">
            <div class="data-panel-icon">
                <i class="bi bi-bar-chart-line"></i>
            </div>
            <div class="flex-grow-1">
                <h2 class="data-panel-title">Rincian Aset per Kategori</h2>
                <p class="data-panel-desc">Total aset dihitung dari stok × harga beli (HPP).</p>
            </div>
            <div class="d-none d-md-flex flex-wrap gap-2 align-items-center">
                <span class="ar-summary-pill ar-summary-pill-warning">
                    Total Aset: <strong>{{ $rp($summary['total_asset_cost']) }}</strong>
                </span>
                <span class="ar-summary-pill ar-summary-pill-primary">
                    Total Stok: <strong>{{ number_format($summary['total_stock']) }} Unit</strong>
                </span>
            </div>
        </div>

        <div class="data-panel-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 ar-table">
                    <thead>
                        <tr>
                            <th class="ps-3" style="width:40px;">No</th>
                            <th>Kategori Barang</th>
                            <th class="text-center" style="width:100px;">Jml Variasi</th>
                            <th class="text-center" style="width:100px;">Total Stok</th>
                            <th class="text-end ar-col-asset" style="width:170px;">Uang Aset (HPP)</th>
                            <th class="text-end" style="width:160px;">Est. Nilai Jual</th>
                            <th class="text-end" style="width:150px;">Potensi Margin</th>
                            <th class="text-center pe-3 no-print" style="width:90px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @forelse ($categories as $cat)
                            @if ($cat['is_selected'])
                                <tr>
                                    <td class="ps-3" style="color:var(--admin-text-muted);">{{ $no++ }}</td>
                                    <td>
                                        <span class="fw-semibold" style="color:var(--admin-text-heading);">{{ $cat['name'] }}</span>
                                        @if (! empty($cat['description']))
                                            <br><small style="color:var(--admin-text-muted);">{{ Str::limit($cat['description'], 55) }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge" style="background:var(--admin-surface-2);color:var(--admin-text-heading);border:1px solid var(--admin-border);min-width:32px;">
                                            {{ number_format($cat['item_count']) }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold" style="color:var(--admin-text-heading);">
                                        {{ number_format($cat['total_stock']) }}
                                    </td>
                                    <td class="text-end font-monospace ar-col-asset">
                                        {{ $rp($cat['asset_cost']) }}
                                    </td>
                                    <td class="text-end font-monospace" style="color:var(--admin-text-heading);font-weight:500;">
                                        {{ $rp($cat['asset_sale']) }}
                                    </td>
                                    <td class="text-end font-monospace" style="color:#4ade80;font-weight:600;">
                                        +{{ $rp($cat['potential_margin']) }}
                                    </td>
                                    <td class="text-center pe-3 no-print">
                                        <button type="button" class="btn btn-sm py-0 px-2"
                                            style="border:1px solid var(--admin-border);color:var(--admin-text-muted);font-size:.78rem;"
                                            data-bs-toggle="modal" data-bs-target="#modal-items-{{ $cat['category_id'] }}"
                                            title="Lihat rincian barang">
                                            <i class="bi bi-list-ul me-1"></i> Rincian
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4" style="color:var(--admin-text-muted);">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Tidak ada kategori yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        {{-- Grand Total --}}
                        <tr class="ar-tfoot-grand">
                            <td colspan="2" class="ps-3 fw-bold text-uppercase" style="color:var(--admin-text-heading);font-size:.9rem;">
                                TOTAL KESELURUHAN ({{ $summary['selected_categories_count'] }} KATEGORI)
                            </td>
                            <td class="text-center fw-bold" style="color:var(--admin-text-heading);">{{ number_format($summary['total_items_count']) }}</td>
                            <td class="text-center fw-bold" style="color:var(--admin-text-heading);">{{ number_format($summary['total_stock']) }}</td>
                            <td class="text-end font-monospace fw-bold ar-col-asset" style="font-size:1rem;">
                                {{ $rp($summary['total_asset_cost']) }}
                            </td>
                            <td class="text-end font-monospace fw-bold" style="color:var(--admin-text-heading);">{{ $rp($summary['total_asset_sale']) }}</td>
                            <td class="text-end font-monospace fw-bold" style="color:#4ade80;">+{{ $rp($summary['total_potential_margin']) }}</td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         MODALS RINCIAN BARANG PER KATEGORI
         ═══════════════════════════════════════════════════ --}}
    @foreach ($categories as $cat)
        @if ($cat['is_selected'])
            <div class="modal fade" id="modal-items-{{ $cat['category_id'] }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content" style="background:var(--admin-surface);border:1px solid var(--admin-border);">
                        <div class="modal-header" style="background:var(--admin-surface-2);border-bottom:1px solid var(--admin-border);">
                            <div>
                                <h5 class="modal-title fw-bold" style="color:var(--admin-text-heading);">
                                    {{ $cat['name'] }}
                                </h5>
                                <small style="color:var(--admin-text-muted);">
                                    Total Aset: <strong class="text-warning">{{ $rp($cat['asset_cost']) }}</strong>
                                    ({{ $cat['item_count'] }} jenis, {{ $cat['total_stock'] }} unit)
                                </small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0 ar-table">
                                    <thead>
                                        <tr>
                                            <th class="ps-3">Kode</th>
                                            <th>Nama Barang</th>
                                            <th class="text-center">Stok</th>
                                            <th class="text-end">Harga Beli</th>
                                            <th class="text-end ar-col-asset">Uang Aset</th>
                                            <th class="text-end">Harga Jual</th>
                                            <th class="text-end pe-3">Nilai Jual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($cat['items'] as $it)
                                            <tr>
                                                <td class="ps-3 font-monospace small" style="color:var(--admin-text-muted);">{{ $it['code'] }}</td>
                                                <td class="fw-semibold" style="color:var(--admin-text-heading);">{{ $it['name'] }}</td>
                                                <td class="text-center fw-bold" style="color:var(--admin-text-heading);">
                                                    {{ number_format($it['stock']) }} <small style="color:var(--admin-text-muted);">{{ $it['unit'] }}</small>
                                                </td>
                                                <td class="text-end font-monospace">{{ $rp($it['purchase_price']) }}</td>
                                                <td class="text-end font-monospace ar-col-asset">{{ $rp($it['asset_cost']) }}</td>
                                                <td class="text-end font-monospace">{{ $rp($it['selling_price']) }}</td>
                                                <td class="text-end font-monospace pe-3" style="color:var(--admin-text-heading);font-weight:500;">{{ $rp($it['asset_sale']) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="text-center py-4" style="color:var(--admin-text-muted);">Belum ada barang aktif.</td></tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="ar-tfoot-grand">
                                            <td colspan="2" class="ps-3 fw-bold" style="color:var(--admin-text-heading);">TOTAL KATEGORI</td>
                                            <td class="text-center fw-bold" style="color:var(--admin-text-heading);">{{ number_format($cat['total_stock']) }}</td>
                                            <td></td>
                                            <td class="text-end font-monospace fw-bold ar-col-asset">{{ $rp($cat['asset_cost']) }}</td>
                                            <td></td>
                                            <td class="text-end font-monospace fw-bold pe-3" style="color:var(--admin-text-heading);">{{ $rp($cat['asset_sale']) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top:1px solid var(--admin-border);">
                            <button type="button" class="btn btn-sm" style="border:1px solid var(--admin-border);color:var(--admin-text-muted);" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endsection

@push('css')
    <style>
        /* ── Quick Buttons ── */
        .ar-quick-btn {
            background: var(--admin-surface-2);
            border: 1px solid var(--admin-border);
            color: var(--admin-text-muted);
            font-weight: 500;
            font-size: .78rem;
            border-radius: 8px;
            padding: .35rem .85rem;
            transition: all .15s;
        }
        .ar-quick-btn:hover {
            border-color: var(--accent);
            color: var(--accent-bright);
            background: var(--accent-muted);
        }
        .ar-quick-btn-primary {
            border-color: rgba(99,102,241,.35);
            color: #818cf8;
            background: rgba(99,102,241,.08);
        }
        .ar-quick-btn-primary:hover {
            border-color: #6366f1;
            color: #a5b4fc;
            background: rgba(99,102,241,.18);
        }
        .ar-quick-btn-info {
            border-color: rgba(6,182,212,.35);
            color: #22d3ee;
            background: rgba(6,182,212,.08);
        }
        .ar-quick-btn-info:hover {
            border-color: #06b6d4;
            color: #67e8f9;
            background: rgba(6,182,212,.18);
        }

        /* ── Chips Kategori ── */
        .ar-chip {
            display: inline-flex;
            align-items: center;
            padding: .3rem .75rem;
            border-radius: 20px;
            font-size: .8rem;
            cursor: pointer;
            user-select: none;
            border: 1px solid var(--admin-border);
            background: var(--admin-surface);
            color: var(--admin-text-muted);
            transition: all .15s;
        }
        .ar-chip:hover {
            border-color: var(--accent);
            color: var(--accent-bright);
        }
        .ar-chip .ar-chip-check { display: none; font-size: .72rem; }
        .ar-chip-active {
            background: var(--accent-soft) !important;
            border-color: var(--accent) !important;
            color: var(--accent-bright) !important;
            font-weight: 600;
        }
        .ar-chip-active .ar-chip-check { display: inline; }

        .ar-chip-badge {
            font-size: .6rem;
            padding: .05rem .35rem;
            border-radius: 8px;
            margin-left: .35rem;
            font-weight: 600;
            line-height: 1.3;
        }
        .ar-chip-badge-primary { background: rgba(99,102,241,.15); color: #818cf8; }
        .ar-chip-badge-info    { background: rgba(6,182,212,.15); color: #22d3ee; }

        /* ── Summary Pills (table header) ── */
        .ar-summary-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .3rem .7rem;
            border-radius: 8px;
            font-size: .75rem;
            font-weight: 500;
        }
        .ar-summary-pill-primary { background: rgba(99,102,241,.1); color: #818cf8; border: 1px solid rgba(99,102,241,.25); }
        .ar-summary-pill-info    { background: rgba(6,182,212,.1); color: #22d3ee; border: 1px solid rgba(6,182,212,.25); }
        .ar-summary-pill-warning { background: var(--accent-muted); color: var(--accent-bright); border: 1px solid rgba(212,160,23,.3); font-weight: 700; }

        /* ── Tabel AR ── */
        .ar-table { border-collapse: separate; border-spacing: 0; width: 100%; }

        .ar-table thead th {
            background: var(--admin-surface-2) !important;
            color: var(--admin-text-muted) !important;
            font-size: .72rem !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: .7rem .6rem;
            border-bottom: 1px solid var(--admin-border) !important;
        }

        .ar-table tbody td {
            padding: .7rem .6rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--admin-border);
            color: var(--admin-text);
        }

        .ar-table tbody tr:hover td {
            background: rgba(255,255,255,.02);
        }

        /* Kolom Aset (Gold highlight) */
        .ar-col-asset {
            background: rgba(212,160,23,.1) !important;
            color: #F0C040 !important;
            font-weight: 700 !important;
            border-left: 1px solid rgba(212,160,23,.2) !important;
            border-right: 1px solid rgba(212,160,23,.2) !important;
        }

        /* Footer rows */
        .ar-tfoot-row td {
            background: var(--admin-surface-2) !important;
            border-top: 1px solid var(--admin-border) !important;
            padding: .65rem .6rem;
            font-size: .85rem;
        }
        .ar-tfoot-grand td {
            background: var(--admin-surface-2) !important;
            border-top: 2px solid var(--admin-border) !important;
            padding: .8rem .6rem;
        }

        /* ── Light Mode Overrides ── */
        [data-bs-theme="light"] .ar-col-asset {
            background: #FFF8E1 !important;
            color: #8C5A14 !important;
            border-color: #FFE082 !important;
        }
        [data-bs-theme="light"] .ar-quick-btn {
            background: #f8f9fa;
            color: #6b7280;
        }
        [data-bs-theme="light"] .ar-chip {
            background: #fff;
            color: #6b7280;
        }
        [data-bs-theme="light"] .ar-chip-active {
            background: #FFF8E1 !important;
            color: #8C5A14 !important;
            border-color: #D4A017 !important;
        }

        /* ── Print ── */
        @media print {
            .no-print, .admin-sidebar, .admin-navbar, .admin-topbar, .page-trail, footer { display: none !important; }
            body { background: #fff !important; color: #000 !important; }
            .data-panel { border: none !important; box-shadow: none !important; }
            .table { font-size: 10px !important; }
        }
    </style>
@endpush

@push('js')
    <script>
        $(function () {
            var $form = $('#filter-form');

            function syncChip(cb) {
                $(cb).closest('.ar-chip').toggleClass('ar-chip-active', cb.checked);
            }

            $(document).on('change', '.category-checkbox', function () {
                syncChip(this);
            });

            // Semua Kategori
            $('#btn-select-all').on('click', function () {
                $('.category-checkbox').prop('checked', true).each(function () { syncChip(this); });
                $form.submit();
            });

            // Kosongkan
            $('#btn-unselect-all').on('click', function () {
                $('.category-checkbox').prop('checked', false).each(function () { syncChip(this); });
                $form.submit();
            });
        });
    </script>
@endpush
