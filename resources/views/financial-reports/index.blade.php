@extends('layouts.app')

@section('title', 'Laporan Keuangan')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Laporan Keuangan'],
        ],
        'title' => 'Laporan Keuangan',
        'subtitle' => 'Pemasukan penjualan dan pengeluaran operasional.',
    ])

    <div class="data-panel mb-4">
        <div class="data-panel-head">
            <div class="data-panel-icon"><i class="bi bi-funnel"></i></div>
            <div class="flex-grow-1">
                <h3 class="data-panel-title">Filter Periode</h3>
                <p class="data-panel-desc">
                    Periode aktif:
                    <strong>{{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}</strong>
                    —
                    <strong>{{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</strong>
                </p>
            </div>
        </div>
        <div class="data-panel-body pt-3">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control form-control-clean" value="{{ $from }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control form-control-clean" value="{{ $to }}" required>
                </div>
                <div class="col-md-6 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i> Terapkan Filter
                    </button>
                    <a href="{{ route('financial-reports.export-pdf', ['from' => $from, 'to' => $to]) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                    </a>
                </div>
            </form>

            {{-- Preset Cepat Periode --}}
            <div class="d-flex flex-wrap gap-1 mt-3 pt-2 border-top">
                <span class="text-muted small me-2 align-self-center">Pilihan Cepat:</span>
                <a href="{{ route('financial-reports.index', ['from' => now()->toDateString(), 'to' => now()->toDateString()]) }}"
                   class="btn btn-xs {{ $from === now()->toDateString() && $to === now()->toDateString() ? 'btn-primary' : 'btn-outline-secondary' }}">Hari Ini</a>
                <a href="{{ route('financial-reports.index', ['from' => now()->startOfWeek()->toDateString(), 'to' => now()->endOfWeek()->toDateString()]) }}"
                   class="btn btn-xs {{ $from === now()->startOfWeek()->toDateString() && $to === now()->endOfWeek()->toDateString() ? 'btn-primary' : 'btn-outline-secondary' }}">Minggu Ini</a>
                <a href="{{ route('financial-reports.index', ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()]) }}"
                   class="btn btn-xs {{ $from === now()->startOfMonth()->toDateString() && $to === now()->endOfMonth()->toDateString() ? 'btn-primary' : 'btn-outline-secondary' }}">Bulan Ini</a>
                <a href="{{ route('financial-reports.index', ['from' => now()->startOfYear()->toDateString(), 'to' => now()->endOfYear()->toDateString()]) }}"
                   class="btn btn-xs {{ $from === now()->startOfYear()->toDateString() && $to === now()->endOfYear()->toDateString() ? 'btn-primary' : 'btn-outline-secondary' }}">Tahun Ini</a>
            </div>
        </div>
    </div>

    @include('financial-reports.partials.report-content-web')
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('css/financial-report.css') }}">
@endpush
