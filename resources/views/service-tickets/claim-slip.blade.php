@extends('layouts.print')

@section('title', 'Nota Service '.$ticket->ticket_no)

@php
    $initialFormat = request('format', 'third');
@endphp

@section('body-class', 'format-'.$initialFormat)

@section('content')
    @php
        $ticket->loadMissing(['customer', 'downPaymentTransaction', 'technician']);
        $dp = $transaction ?? $ticket->downPaymentTransaction;
        $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
        $receivedAt = $ticket->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i');
        $device = trim($ticket->brand.' '.$ticket->model);
        if ($ticket->color) {
            $device .= ' ('.$ticket->color.')';
        }
        $dpAmount = $dp && ! $dp->isRefunded()
            ? (float) $dp->total
            : $ticket->downPaymentAmount();
        $hasDp = $dpAmount > 0;
        $sisa = $ticket->estimated_cost !== null
            ? round(max(0, (float) $ticket->estimated_cost - $dpAmount), 2)
            : null;

        $terbilangText = function_exists('terbilang') && $dpAmount > 0
            ? '# '.terbilang($dpAmount).' Rupiah #'
            : '';
    @endphp

    {{-- TOOLBAR KONTROL CETAK & FORMAT (Hanya tampil di layar, tidak tercetak) --}}
    <div class="ncr-toolbar no-print">
        <div class="ncr-toolbar-group">
            <span style="font-weight: 600; color: #94a3b8; margin-right: 4px;">Ukuran:</span>
            <button type="button" class="btn-ncr btn-ncr--active" id="btn-toggle-third" onclick="switchFormat('third')">
                <i class="bi bi-file-earmark-text"></i> 1/3 Folio (21.5 × 11 cm)
            </button>
            <button type="button" class="btn-ncr" id="btn-toggle-dotmatrix" onclick="switchFormat('dotmatrix')">
                <i class="bi bi-printer"></i> 1/2 Folio / NCR (21.5 × 14 cm)
            </button>
            <button type="button" class="btn-ncr" id="btn-toggle-thermal" onclick="switchFormat('thermal')">
                <i class="bi bi-receipt"></i> Struk Thermal (58mm)
            </button>
        </div>
        <div class="ncr-toolbar-group">
            <span style="font-weight: 600; color: #94a3b8; margin-right: 4px;">Orientasi:</span>
            <button type="button" class="btn-ncr btn-ncr--active" id="btn-orient-portrait" onclick="switchOrientation('portrait')">
                <i class="bi bi-file-earmark"></i> Potret (Tegak)
            </button>
            <button type="button" class="btn-ncr" id="btn-orient-landscape" onclick="switchOrientation('landscape')">
                <i class="bi bi-file-earmark-landscape"></i> Lanskap
            </button>
        </div>
        <div class="ncr-toolbar-group">
            <button type="button" class="btn-ncr btn-ncr--primary" onclick="window.print()">
                <strong>Cetak Sekarang</strong>
            </button>
            <button type="button" class="btn-ncr btn-ncr--light" onclick="window.close()">Tutup</button>
        </div>
    </div>

    {{-- =========================================================================
         TAMPILAN 1: FORMAT LEMBARAN 1/3 FOLIO (21.5 x 11 CM) & 1/2 FOLIO (21.5 x 14 CM)
         ========================================================================= --}}
    <div class="format-dotmatrix-view">
        <div class="ncr-paper-wrapper">
            {{-- EMBOSS / WATERMARK ARCell DI TENGAH NOTA --}}
            <div class="ncr-watermark" aria-hidden="true">ARCell</div>

            <div class="ncr-sheet">
                {{-- HEADER TOKO & JUDUL NOTA --}}
                <div class="ncr-header">
                    <div class="ncr-brand">
                        <div class="ncr-brand-name">{{ brand_name() }}</div>
                        <div class="ncr-brand-sub">{{ brand_tagline() ?: 'Service Smartphone & Laptop' }}</div>
                        <div class="ncr-brand-contact">
                            jl. Perwira no.8 kapangsari Petarukan<br>
                            Hp: 082322872201 / 087733300186
                        </div>
                    </div>
                    <div class="ncr-title-box">
                        <div class="ncr-title">TANDA TERIMA SERVICE</div>
                        <div class="ncr-title-meta">No. Tiket : <strong>{{ $ticket->ticket_no }}</strong></div>
                        <div class="ncr-title-meta">Tgl Masuk : {{ $receivedAt }}</div>
                        <div class="ncr-title-meta">Teknisi   : {{ $ticket->technician?->name ?? 'Belum Ditugaskan' }}</div>
                    </div>
                </div>

                {{-- INFO PELANGGAN & PERANGKAT --}}
                <div class="ncr-info-grid">
                    <div class="ncr-info-col">
                        <div class="ncr-info-row">
                            <span class="ncr-info-label">Nama Pelanggan</span>
                            <span class="ncr-info-colon">:</span>
                            <span class="ncr-info-value">{{ $ticket->customer?->name ?? 'Pelanggan Umum' }}</span>
                        </div>
                        <div class="ncr-info-row">
                            <span class="ncr-info-label">No. Telepon / WA</span>
                            <span class="ncr-info-colon">:</span>
                            <span class="ncr-info-value">{{ $ticket->customer?->phone ?? '-' }}</span>
                        </div>
                        <div class="ncr-info-row">
                            <span class="ncr-info-label">Alamat</span>
                            <span class="ncr-info-colon">:</span>
                            <span class="ncr-info-value">{{ $ticket->customer?->address ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="ncr-info-col">
                        <div class="ncr-info-row">
                            <span class="ncr-info-label">Tipe Unit</span>
                            <span class="ncr-info-colon">:</span>
                            <span class="ncr-info-value">{{ $ticket->deviceTypeLabel() }} — {{ $device }}</span>
                        </div>
                        <div class="ncr-info-row">
                            <span class="ncr-info-label">IMEI / No Seri</span>
                            <span class="ncr-info-colon">:</span>
                            <span class="ncr-info-value">{{ $ticket->serial_imei ?: '-' }}</span>
                        </div>
                        <div class="ncr-info-row">
                            <span class="ncr-info-label">Kelengkapan</span>
                            <span class="ncr-info-colon">:</span>
                            <span class="ncr-info-value">{{ $ticket->accessories ?: 'Unit saja' }}</span>
                        </div>
                    </div>
                </div>

                {{-- TABEL DESKRIPSI KELUHAN & DIAGNOSA AWAL --}}
                <table class="ncr-table">
                    <thead>
                        <tr>
                            <th class="text-left" style="width: 50%;">KELUHAN / KERUSAKAN BARANG</th>
                            <th class="text-left" style="width: 50%;">DIAGNOSA AWAL / SPESIFIKASI TAMBAHAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-left" style="padding: 6px 5px; min-height: 35px;">
                                <strong>{{ $ticket->complaint }}</strong>
                            </td>
                            <td class="text-left" style="padding: 6px 5px;">
                                {{ $ticket->diagnosis ?: ($ticket->specs ? 'Spesifikasi: '.$ticket->specs : 'Menunggu pengecekan teknisi.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                {{-- BAGIAN BAWAH: KETENTUAN, TANDA TANGAN & BIAYA --}}
                <div class="ncr-bottom">
                    <div class="ncr-bottom-left">
                        @if ($terbilangText && $hasDp)
                            <div class="ncr-terbilang-box">
                                <strong>Terbilang Uang Muka:</strong> {{ $terbilangText }}
                            </div>
                        @endif

                        <div class="ncr-terms">
                            <strong>Syarat & Ketentuan Servis:</strong><br>
                            1. Pengambilan unit <strong>WAJIB</strong> menunjukkan lembar nota tanda terima ini.<br>
                            2. Barang yang tidak diambil dalam tempo 30 hari setelah selesai di luar tanggung jawab toko.<br>
                            3. Kerusakan komponen internal saat pengerjaan akibat korosi/benturan awal bukan kelalaian teknisi.<br>
                            4. Lacak status servis online: <strong>{{ $ticket->trackingUrl() }}</strong>
                        </div>

                        <div class="ncr-signatures">
                            <div class="ncr-sign-col">
                                <div>Pemilik / Pelanggan,</div>
                                <div class="ncr-sign-space"></div>
                                <div class="ncr-sign-name">( {{ $ticket->customer?->name ?? 'Pelanggan' }} )</div>
                            </div>
                            <div class="ncr-sign-col">
                                <div>Penerima / {{ brand_name() }},</div>
                                <div class="ncr-sign-space"></div>
                                <div class="ncr-sign-name">( {{ brand_name() }} )</div>
                            </div>
                        </div>
                    </div>

                    <div class="ncr-bottom-right">
                        <table class="ncr-summary-table">
                            @if ($ticket->estimated_cost !== null)
                                <tr>
                                    <td>Estimasi Biaya</td>
                                    <td class="text-right">{{ $rp($ticket->estimated_cost) }}</td>
                                </tr>
                            @else
                                <tr>
                                    <td>Estimasi Biaya</td>
                                    <td class="text-right">Menunggu Konfirmasi</td>
                                </tr>
                            @endif

                            @if ($hasDp)
                                <tr>
                                    <td>Uang Muka (DP)</td>
                                    <td class="text-right">- {{ $rp($dpAmount) }}</td>
                                </tr>
                                <tr class="ncr-summary-total">
                                    <td>SISA ESTIMASI</td>
                                    <td class="text-right">{{ $rp($sisa ?? 0) }}</td>
                                </tr>
                            @else
                                <tr class="ncr-summary-total">
                                    <td>UANG MUKA (DP)</td>
                                    <td class="text-right">Rp 0 (Belum Ada)</td>
                                </tr>
                            @endif

                            <tr>
                                <td colspan="2" class="text-right" style="padding-top: 4px;">
                                    <div class="ncr-payment-badge">
                                        {{ $hasDp ? 'DP DITERIMA' : 'UNIT DITERIMA' }}
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         TAMPILAN 2: FORMAT THERMAL (58MM)
         ========================================================================= --}}
    <div class="format-thermal-view">
        <article class="receipt-thermal">
            @if (brand_has_custom_logo())
                <div class="receipt-center receipt-logo-wrap">
                    <img src="{{ brand_logo_url() }}" alt="{{ brand_name() }}" class="receipt-logo">
                </div>
            @endif
            <div class="receipt-center receipt-brand">{{ brand_name() }}</div>
            <div class="receipt-center receipt-title">NOTA SERVICE</div>
            <div class="receipt-center receipt-meta">{{ $ticket->ticket_no }} · {{ $receivedAt }}</div>

            <div class="receipt-sep"></div>

            <div class="receipt-text receipt-text--strong">{{ $ticket->customer?->name }}</div>
            @if ($ticket->customer?->phone)
                <div class="receipt-text receipt-text--muted">{{ $ticket->customer->phone }}</div>
            @endif

            <div class="receipt-sep"></div>

            <div class="receipt-text receipt-text--strong">{{ $device }}</div>
            @if ($ticket->serial_imei)
                <div class="receipt-text">IMEI {{ $ticket->serial_imei }}</div>
            @endif
            @if ($ticket->accessories)
                <div class="receipt-text receipt-text--muted">Kelengkapan: {{ $ticket->accessories }}</div>
            @endif

            <div class="receipt-sep"></div>
            <div class="receipt-text"><strong>Keluhan:</strong> {{ $ticket->complaint }}</div>

            @if ($hasDp || $ticket->estimated_cost !== null)
                <div class="receipt-sep"></div>
                @if ($hasDp)
                    <div class="receipt-row receipt-row--tight">
                        <span>Uang muka</span>
                        <span>{{ $rp($dpAmount) }}</span>
                    </div>
                @endif
                @if ($ticket->estimated_cost !== null)
                    <div class="receipt-row receipt-row--total">
                        <span>{{ $hasDp ? 'Sisa' : 'Estimasi' }}</span>
                        <span>{{ $rp($hasDp ? $sisa : $ticket->estimated_cost) }}</span>
                    </div>
                @endif
            @endif

            <div class="receipt-sep"></div>
            <div class="receipt-center receipt-footer">Tunjukkan struk ini saat ambil barang</div>
        </article>
    </div>
@endsection

@push('js')
    <script>
        let currentFormat = 'third';
        let currentOrientation = 'portrait';

        function updatePrintStyle() {
            const styleTag = document.getElementById('dynamic-page-size');
            if (!styleTag) return;

            if (currentFormat === 'thermal') {
                styleTag.innerHTML = '@media print { @page { size: 58mm auto; margin: 1mm !important; } }';
            } else {
                styleTag.innerHTML = `@media print { @page { size: ${currentOrientation}; margin: 0 !important; } }`;
            }
        }

        function switchOrientation(orient) {
            currentOrientation = orient;
            const btnPort = document.getElementById('btn-orient-portrait');
            const btnLand = document.getElementById('btn-orient-landscape');
            if (btnPort) btnPort.classList.toggle('btn-ncr--active', orient === 'portrait');
            if (btnLand) btnLand.classList.toggle('btn-ncr--active', orient === 'landscape');

            document.body.classList.remove('orient-portrait', 'orient-landscape');
            document.body.classList.add('orient-' + orient);

            updatePrintStyle();
            try {
                localStorage.setItem('ar_invoice_orientation', orient);
            } catch (e) {}
        }

        function switchFormat(format) {
            currentFormat = format;
            document.body.classList.remove('format-third', 'format-dotmatrix', 'format-thermal');
            document.body.classList.add('format-' + format);

            const btnThird = document.getElementById('btn-toggle-third');
            const btnDot = document.getElementById('btn-toggle-dotmatrix');
            const btnThermal = document.getElementById('btn-toggle-thermal');

            if (btnThird) btnThird.classList.toggle('btn-ncr--active', format === 'third');
            if (btnDot) btnDot.classList.toggle('btn-ncr--active', format === 'dotmatrix');
            if (btnThermal) btnThermal.classList.toggle('btn-ncr--active', format === 'thermal');

            updatePrintStyle();
            try {
                localStorage.setItem('ar_invoice_format', format);
            } catch (e) {}
        }

        // Inisialisasi format dan orientasi tersimpan
        (function() {
            try {
                const savedFormat = localStorage.getItem('ar_invoice_format');
                const savedOrient = localStorage.getItem('ar_invoice_orientation');
                const urlParam = new URLSearchParams(window.location.search).get('format');
                const urlOrient = new URLSearchParams(window.location.search).get('orientation');

                const targetFormat = urlParam || savedFormat || 'third';
                const targetOrient = urlOrient || savedOrient || 'portrait';

                switchFormat(targetFormat);
                switchOrientation(targetOrient);
            } catch (e) {}
        })();

        @if (request()->boolean('print'))
            window.addEventListener('load', function () {
                window.print();
            });
        @endif
    </script>
@endpush
