@extends('layouts.app')

@section('title', 'Daftar Inventaris')
@section('page-title', 'Inventaris')

@section('content')

<style>
.page-hero {
    background: linear-gradient(135deg, #222630 0%, #1A1D24 100%);
    border-radius: var(--radius-lg);
    padding: 24px 28px;
    color: white;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border-left: 5px solid var(--brand-orange);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}
.page-hero h1 {
    font-size: 22px;
    font-weight: 800;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.page-hero h1 i { color: var(--brand-orange); }
.page-hero p { font-size: 13px; color: #94A3B8; }
</style>

{{-- Page Hero Header --}}
<div class="page-hero">
    <div>
        <h1><i class="fas fa-boxes-stacked"></i> Data Inventaris Aset</h1>
        <p>Kelola dan monitor seluruh inventaris sewa beli PT Banyan Koalindo Lestari</p>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="{{ route('inventaris.export.pdf', request()->query()) }}" id="btnExportPdf" data-base="{{ route('inventaris.export.pdf') }}" class="btn btn-secondary" target="_blank">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
        <a href="{{ route('inventaris.export.excel', request()->query()) }}" id="btnExportExcel" data-base="{{ route('inventaris.export.excel') }}" class="btn btn-secondary">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <a href="{{ route('inventaris.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Inventaris
        </a>
    </div>
</div>

<div class="table-section">
    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('inventaris.index') }}" id="filterForm">
        <div class="filter-bar">
            <div class="filter-group" style="flex:1; min-width:220px;">
                <div class="topbar-search" style="width:100%;">
                    <div class="search-form" style="width:100%;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" id="searchInventaris" value="{{ request('search') }}"
                               placeholder="Cari kode, nama barang, vendor, lokasi..."
                               class="search-input" style="width:100%;" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="filter-group">
                <span class="filter-label">Status Aset</span>
                <select name="status_aset" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="Ada" {{ request('status_aset') === 'Ada' ? 'selected' : '' }}>Ada</option>
                    <option value="Hilang" {{ request('status_aset') === 'Hilang' ? 'selected' : '' }}>Hilang</option>
                    <option value="Rusak" {{ request('status_aset') === 'Rusak' ? 'selected' : '' }}>Rusak</option>
                    <option value="Tidak Ditemukan" {{ request('status_aset') === 'Tidak Ditemukan' ? 'selected' : '' }}>Tidak Ditemukan</option>
                </select>
            </div>

            <div class="filter-group">
                <span class="filter-label">Status Kontrak</span>
                <select name="status_kontrak" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Kontrak</option>
                    <option value="Sedang Berjalan" {{ request('status_kontrak') === 'Sedang Berjalan' ? 'selected' : '' }}>Sedang Berjalan</option>
                    <option value="Akan Berakhir" {{ request('status_kontrak') === 'Akan Berakhir' ? 'selected' : '' }}>Akan Berakhir</option>
                    <option value="Habis" {{ request('status_kontrak') === 'Habis' ? 'selected' : '' }}>Habis</option>
                </select>
            </div>

            <div class="filter-group">
                <span class="filter-label">Kategori</span>
                <select name="kategori" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoris as $kat)
                    <option value="{{ $kat }}" {{ request('kategori') === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <span class="filter-label">Lokasi</span>
                <select name="lokasi" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Lokasi</option>
                    @foreach($lokasis as $lok)
                    <option value="{{ $lok }}" {{ request('lokasi') === $lok ? 'selected' : '' }}>{{ $lok }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-search"></i> Cari
            </button>
            @if(request()->anyFilled(['search', 'status_aset', 'status_kontrak', 'kategori', 'vendor', 'lokasi']))
            <a href="{{ route('inventaris.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-xmark"></i> Reset
            </a>
            @endif
        </div>
    </form>

    {{-- Tabel + pagination (diganti oleh live search) --}}
    <div id="inventarisTable">
        @include('inventaris.partials.table')
    </div>
</div>

@include('partials.delete-modal')
@endsection

@push('scripts')
<script>
// Live search inventaris (vanilla JS)
(function () {
    const form = document.getElementById('filterForm');
    const searchInput = document.getElementById('searchInventaris');
    const tableWrapper = document.getElementById('inventarisTable');
    const exportPdf = document.getElementById('btnExportPdf');
    const exportExcel = document.getElementById('btnExportExcel');

    let timer = null;
    let controller = null;

    function loadTable() {
        // Ambil semua nilai filter dari form (search + dropdown), abaikan yang kosong
        const params = new URLSearchParams();
        new FormData(form).forEach(function (value, key) {
            if (value) params.append(key, value);
        });
        const qs = params.toString() ? '?' + params.toString() : '';
        const url = form.action + qs;

        // Batalkan request sebelumnya kalau masih berjalan
        if (controller) controller.abort();
        controller = new AbortController();

        tableWrapper.style.opacity = '0.5';

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal
        })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.text();
            })
            .then(function (html) {
                tableWrapper.innerHTML = html;
                tableWrapper.style.opacity = '1';

                // Update URL & link export supaya ikut search yang aktif
                history.replaceState(null, '', url);
                exportPdf.href = exportPdf.dataset.base + qs;
                exportExcel.href = exportExcel.dataset.base + qs;
            })
            .catch(function (err) {
                if (err.name === 'AbortError') return;
                tableWrapper.style.opacity = '1';
                console.error('Gagal memuat data inventaris:', err);
            });
    }

    function onSearchInput(e) {
        // Samakan isi kedua kotak search (filter bar & header atas)
        searchInput.value = e.target.value;
        if (globalSearch) globalSearch.value = e.target.value;

        clearTimeout(timer);
        timer = setTimeout(loadTable, 300);
    }

    searchInput.addEventListener('input', onSearchInput);

    // Kotak search di header atas juga ikut live di halaman ini
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        globalSearch.addEventListener('input', onSearchInput);
    }
})();
</script>
@endpush
