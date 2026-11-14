@php
    $ticket = $ticket ?? null;
    $loggedInTechnician = auth()->user()?->resolveTechnician();
    $activeTechnicianId = $ticket?->technician_id ?: $loggedInTechnician?->id;
    $ticketPrefill = $ticket ? [
        'id' => $ticket->id,
        'ticket_no' => $ticket->ticket_no,
        'customer_id' => $ticket->customer_id,
        'technician_id' => $activeTechnicianId,
        'notes' => $ticket->invoiceNotes(),
        'down_payment' => $ticket->downPaymentAmount(),
        'warranty_days' => $ticket->warranty_days,
        'warranty_notes' => $ticket->warranty_notes,
        'redirectUrl' => route('service-tickets.show', $ticket),
        'items' => $ticket->relationLoaded('itemLines') ? $ticket->workItemsPayload() : [],
        'services' => $ticket->relationLoaded('serviceLines') ? $ticket->workServicesPayload() : [],
    ] : null;
@endphp
@extends('layouts.app')

@section('title', 'Transaksi Baru')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Transaksi Penjualan', 'url' => route('transactions.index')],
            ['label' => 'Baru'],
        ],
        'title' => $ticket ? 'Nota Tiket '.$ticket->ticket_no : 'Transaksi Baru',
        'subtitle' => 'Penjualan barang dan sparepart.',
    ])

    <form id="transaction-form">
        @csrf
        @if ($ticket)
            <input type="hidden" id="service_ticket_id" value="{{ $ticket->id }}">
            <div class="alert alert-light border mb-3">
                <div class="fw-semibold mb-1"><i class="bi bi-phone me-1"></i> Nota untuk tiket {{ $ticket->ticket_no }}</div>
                <div class="small mb-0">
                    {{ $ticket->deviceSummary() }} — {{ $ticket->customer?->name }}.
                    Tambah <strong>jasa servis</strong> saja, atau jasa plus <strong>sparepart</strong> di nota yang sama.
                    @if ($ticket->estimated_cost !== null)
                        Estimasi: Rp {{ number_format((float) $ticket->estimated_cost, 0, ',', '.') }}.
                    @endif
                    @if ($ticket->hasDownPayment())
                        Uang muka: Rp {{ number_format($ticket->downPaymentAmount(), 0, ',', '.') }} — dipotong dari sisa bayar.
                    @endif
                </div>
            </div>
        @else
            <div class="tx-order-tabs mb-3" id="tx-order-tabs">
            <div class="tx-order-tabs-scroll">
                <div class="tx-order-tabs-list" id="tx-order-tabs-list">
                    <button type="button" class="tx-order-tab-add" id="btn-add-tab" title="Transaksi baru">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </div>
            <div class="form-hint-sm mt-2 mb-0">Pindah tab untuk melayani transaksi lain secara bersamaan. Stok dicek saat bayar/submit.</div>
        </div>
        @endif
        <div class="row g-4 align-items-lg-start">
            <div class="col-lg-8">
                {{-- Mode tanpa pelanggan: otomatis ke Umum (walk-in) --}}
                <input type="hidden" id="customer_id" value="__umum__">
                <input type="hidden" id="new_customer_name" value="">
                <input type="hidden" id="new_customer_phone" value="">
                <input type="hidden" id="new_customer_address" value="">

                <div class="data-panel mb-4" id="items-section">
                    <div class="data-panel-head">
                        <h2 class="data-panel-title"><i class="bi bi-box-seam me-1"></i> Data Barang</h2>
                    </div>
                    <div class="data-panel-body">
                        <div id="items-section-lock" class="items-section-empty text-center text-muted py-4 px-3 d-none">
                            <i class="bi bi-person-check fs-4 d-block mb-2"></i>
                            <div class="fw-medium">Pilih barang untuk transaksi</div>
                        </div>
                        <div id="items-add-form" class="stock-cart-add border rounded p-3 mb-3">
                            <div class="cart-add-row row g-2">
                                <div class="col-md-7">
                                    <label class="form-label small mb-0" for="item_select">Pilih Barang</label>
                                    <select id="item_select" class="form-control-clean cart-add-control atha-searchable-select atha-select2-item">
                                        <option value="">-- Pilih Barang --</option>
                                        @foreach ($items as $item)
                                            @include('transactions.include.item-option', ['item' => $item])
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-0" for="item_qty">Qty</label>
                                    <input type="number" id="item_qty" class="form-control form-control-clean cart-add-control" min="1" placeholder="1" value="1">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0 cart-add-action-label" aria-hidden="true">Aksi</label>
                                    <button type="button" class="btn btn-outline-primary w-100 cart-add-btn" id="btn-add-item">
                                        <i class="bi bi-cart-plus"></i>
                                    </button>
                                </div>
                                <div class="col-12">
                                    <div class="cart-add-hint" id="item-hint"></div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Barang</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Harga</th>
                                        <th class="text-end">Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="items-cart-body"></tbody>
                            </table>
                            <div id="items-cart-empty" class="text-center text-muted py-3 small">Belum ada barang.</div>
                        </div>
                    </div>
                </div>

                {{-- Hidden fallback container untuk kompatibilitas script keranjang transaksi --}}
                <div class="d-none" id="services-cart-container" aria-hidden="true">
                    <select id="service_select"><option value=""></option></select>
                    <input type="number" id="service_qty" value="1">
                    <button type="button" id="btn-add-service"></button>
                    <table><tbody id="services-cart-body"></tbody></table>
                    <div id="services-cart-empty"></div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="data-panel tx-summary-sticky">
                    <div class="data-panel-head">
                        <h2 class="data-panel-title"><i class="bi bi-receipt me-1"></i> Ringkasan</h2>
                    </div>
                    <div class="data-panel-body">
                        @include('transactions.partials.summary-fields', [
                            'ticket' => $ticket,
                            'technicians' => $technicians,
                            'bankAccounts' => $bankAccounts,
                            'submitLabel' => 'Bayar & Selesai',
                            'submitDisabled' => true,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('js')
    <script src="{{ asset('js/payment-fields.js') }}"></script>
    <script src="{{ asset('js/transaction-cart.js') }}?v={{ filemtime(public_path('js/transaction-cart.js')) }}"></script>
    <script>
        AthaPaymentFields.init();
        AthaTransactionCart.init({
            storeUrl: '{{ route('transactions.store') }}',
            itemAvailabilityUrl: '{{ route('transactions.items.availability') }}',
            showUrlTemplate: '{{ route('transactions.show', '__ID__') }}',
            redirectUrl: '{{ route('transactions.index') }}',
            techPercent: {{ (int) config('workshop.default_technician_commission_percent', 20) }},
            ownerPercent: {{ 100 - (int) config('workshop.default_technician_commission_percent', 20) }},
            defaultTechPercent: {{ (int) config('workshop.default_technician_commission_percent', 20) }},
            items: @json($items),
            services: @json($services),
            ticket: @json($ticketPrefill),
        });
    </script>
@endpush
