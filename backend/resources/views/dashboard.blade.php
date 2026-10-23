@extends('layouts.app')

@section('title', 'Dashboard Monitoring')
@section('page-title', 'Dashboard')

@section('content')

<style>
.dashboard-hero {
    background: linear-gradient(135deg, #222630 0%, #1A1D24 100%);
    border-radius: var(--radius-lg);
    padding: 28px 32px;
    color: white;
    margin-bottom: 28px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border-left: 5px solid var(--brand-orange);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
}
.hero-text h1 {
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -0.5px;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.hero-text h1 i {
    color: var(--brand-orange);
}
.hero-text p {
    font-size: 13.5px;
    color: #94A3B8;
    max-width: 550px;
    line-height: 1.5;
}
.hero-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.metrics-summary-bar {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 28px;
}
.metric-box {
    background: white;
    border-radius: var(--radius-lg);
    padding: 20px 24px;
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow-card);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.metric-box-left span {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--brand-gray);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.metric-box-left h3 {
    font-size: 22px;
    font-weight: 800;
    color: var(--gray-900);
    margin-top: 4px;
}
.metric-box-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-md);
    background: var(--brand-orange-light);
    color: var(--brand-orange);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.stats-grid-v2 {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 14px;
    margin-bottom: 28px;
}
@media (max-width: 1400px) {
    .stats-grid-v2 {
        grid-template-columns: repeat(4, 1fr);
    }
}
@media (max-width: 900px) {
    .stats-grid-v2 {
        grid-template-columns: repeat(2, 1fr);
    }
    .metrics-summary-bar {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 500px) {
    .stats-grid-v2 {
        grid-template-columns: 1fr;
    }
}

.stat-card-v2 {
    background: white;
    border-radius: var(--radius-lg);
    padding: 16px;
    box-shadow: var(--shadow-card);
    border: 1.5px solid var(--gray-200);
    cursor: pointer;
    transition: var(--transition);
    position: relative;
}
.stat-card-v2:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-card-hover);
    border-color: var(--brand-orange-border);
}
.stat-card-v2.card-active {
    border-color: var(--brand-orange);
    box-shadow: 0 0 0 3px var(--brand-orange-glow), var(--shadow-card-hover);
    background: var(--brand-orange-light);
}
.stat-v2-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.stat-v2-icon {
    width: 36px;
    height: 36px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}
.stat-v2-val {
    font-size: 24px;
    font-weight: 800;
    color: var(--gray-900);
    line-height: 1;
}
.stat-v2-lbl {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--brand-gray);
    margin-top: 6px;
}

/* Colors for Stat Cards V2 */
.card-v2-total .stat-v2-icon { background: var(--brand-orange-light); color: var(--brand-orange); }
.card-v2-ada .stat-v2-icon { background: var(--status-ada-bg); color: var(--status-ada); }
.card-v2-hilang .stat-v2-icon { background: var(--status-hilang-bg); color: var(--status-hilang); }
.card-v2-rusak .stat-v2-icon { background: var(--status-rusak-bg); color: var(--status-rusak); }
.card-v2-berjalan .stat-v2-icon { background: var(--status-berjalan-bg); color: var(--status-berjalan); }
.card-v2-akan .stat-v2-icon { background: var(--status-akan-bg); color: var(--status-akan); }
.card-v2-habis .stat-v2-icon { background: var(--status-habis-bg); color: var(--status-habis); }
</style>

{{-- Hero Header --}}
<div class="dashboard-hero">
    <div class="hero-text">
        <h1>
            <i class="fas fa-chart-line"></i>
            Dashboard Monitoring Inventaris
        </h1>
        <p>Sistem Informasi Monitoring Inventaris Sewa Beli — PT Banyan Koalindo Lestari</p>
    </div>
    <div class="hero-actions">
        <a href="{{ route('inventaris.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Inventaris
        </a>
        <a href="{{ route('dashboard.export.pdf', ['filter' => $filter]) }}" class="btn btn-secondary" target="_blank">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
        <a href="{{ route('dashboard.export.excel', ['filter' => $filter]) }}" class="btn btn-secondary">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
    </div>
</div>

{{-- Summary Financial & Contract Bar --}}
<div class="metrics-summary-bar">
    <div class="metric-box">
        <div class="metric-box-left">
            <span>Total Kontrak Sewa Beli Terdaftar</span>
            <h3>{{ $totalKontrak }} Kontrak Aktif</h3>
        </div>
        <div class="metric-box-icon">
            <i class="fas fa-file-contract"></i>
        </div>
    </div>
    <div class="metric-box">
        <div class="metric-box-left">
            <span>Est. Total Biaya Sewa per Bulan</span>
            <h3 style="color: var(--brand-orange);">Rp {{ number_format($totalBiayaSewa, 0, ',', '.') }}</h3>
        </div>
        <div class="metric-box-icon" style="background: var(--status-ada-bg); color: var(--status-ada);">
            <i class="fas fa-wallet"></i>
        </div>
    </div>
</div>

{{-- Interactive Filter Stat Cards --}}
<div class="stats-grid-v2">
    {{-- Total Inventaris --}}
    <div class="stat-card-v2 card-v2-total {{ $filter === 'terbaru' ? 'card-active' : '' }}"
         onclick="filterDashboard('terbaru', this)"
         data-tooltip="Klik untuk lihat semua inventaris">
        <div class="stat-v2-header">
            <div class="stat-v2-icon"><i class="fas fa-boxes-stacked"></i></div>
        </div>
        <div class="stat-v2-val">{{ $totalInventaris }}</div>
        <div class="stat-v2-lbl">Total Inventaris</div>
    </div>

    {{-- Aset Ada --}}
    <div class="stat-card-v2 card-v2-ada {{ $filter === 'ada' ? 'card-active' : '' }}"
         onclick="filterDashboard('ada', this)"
         data-tooltip="Klik untuk lihat aset yang ada">
        <div class="stat-v2-header">
            <div class="stat-v2-icon"><i class="fas fa-circle-check"></i></div>
        </div>
        <div class="stat-v2-val">{{ $asetAda }}</div>
        <div class="stat-v2-lbl">Aset Ada</div>
    </div>

    {{-- Aset Hilang --}}
    <div class="stat-card-v2 card-v2-hilang {{ $filter === 'hilang' ? 'card-active' : '' }}"
         onclick="filterDashboard('hilang', this)"
         data-tooltip="Klik untuk lihat aset hilang">
        <div class="stat-v2-header">
            <div class="stat-v2-icon"><i class="fas fa-triangle-exclamation"></i></div>
        </div>
        <div class="stat-v2-val">{{ $asetHilang }}</div>
        <div class="stat-v2-lbl">Aset Hilang</div>
    </div>

    {{-- Aset Rusak --}}
    <div class="stat-card-v2 card-v2-rusak {{ $filter === 'rusak' ? 'card-active' : '' }}"
         onclick="filterDashboard('rusak', this)"
         data-tooltip="Klik untuk lihat aset rusak">
        <div class="stat-v2-header">
            <div class="stat-v2-icon"><i class="fas fa-wrench"></i></div>
        </div>
        <div class="stat-v2-val">{{ $asetRusak }}</div>
        <div class="stat-v2-lbl">Aset Rusak</div>
    </div>

    {{-- Kontrak Berjalan --}}
    <div class="stat-card-v2 card-v2-berjalan {{ $filter === 'kontrak_berjalan' ? 'card-active' : '' }}"
         onclick="filterDashboard('kontrak_berjalan', this)"
         data-tooltip="Klik untuk lihat kontrak aktif">
        <div class="stat-v2-header">
            <div class="stat-v2-icon"><i class="fas fa-file-signature"></i></div>
        </div>
        <div class="stat-v2-val">{{ $kontrakSedangBerjalan }}</div>
        <div class="stat-v2-lbl">Kontrak Berjalan</div>
    </div>

    {{-- Kontrak Akan Berakhir --}}
    <div class="stat-card-v2 card-v2-akan {{ $filter === 'kontrak_akan_berakhir' ? 'card-active' : '' }}"
         onclick="filterDashboard('kontrak_akan_berakhir', this)"
         data-tooltip="Klik untuk lihat kontrak akan berakhir">
        <div class="stat-v2-header">
            <div class="stat-v2-icon"><i class="fas fa-clock"></i></div>
        </div>
        <div class="stat-v2-val">{{ $kontrakAkanBerakhir }}</div>
        <div class="stat-v2-lbl">Akan Berakhir</div>
    </div>

    {{-- Kontrak Habis --}}
    <div class="stat-card-v2 card-v2-habis {{ $filter === 'kontrak_habis' ? 'card-active' : '' }}"
         onclick="filterDashboard('kontrak_habis', this)"
         data-tooltip="Klik untuk lihat kontrak habis">
        <div class="stat-v2-header">
            <div class="stat-v2-icon"><i class="fas fa-calendar-xmark"></i></div>
        </div>
        <div class="stat-v2-val">{{ $kontrakHabis }}</div>
        <div class="stat-v2-lbl">Kontrak Habis</div>
    </div>
</div>

{{-- Filtered Table List --}}
<div class="table-section" id="dashboardList">
    @include('dashboard.list', ['listInventaris' => $listInventaris, 'listTitle' => $listTitle])
</div>

{{-- Delete Modal --}}
@include('partials.delete-modal')

@endsection

@push('scripts')
<script>
function filterDashboard(filterType, element) {
    // Remove active state from all cards
    document.querySelectorAll('.stat-card-v2').forEach(card => card.classList.remove('card-active'));
    // Add active state to clicked card
    if (element) element.classList.add('card-active');

    // Fetch filtered list via AJAX or reload with parameter
    window.location.href = "{{ route('dashboard') }}?filter=" + filterType;
}
</script>
@endpush
