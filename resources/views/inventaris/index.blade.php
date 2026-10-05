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
        <a href="{{ route('inventaris.export.pdf', request()->query()) }}" class="btn btn-secondary" target="_blank">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
        <a href="{{ route('inventaris.export.excel', request()->query()) }}" class="btn btn-secondary">
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
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Cari kode, nama barang, vendor, lokasi..."
                               class="search-input" style="width:100%;">
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

    {{-- Table Header --}}
    <div class="table-header">
        <div class="table-title">
            <i class="fas fa-layer-group" style="color: var(--brand-orange);"></i>
            <span>Daftar Seluruh Inventaris</span>
            <span class="title-badge">{{ $inventarisList->total() }} Item Total</span>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">Foto</th>
                    <th>Kode Inventaris</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Vendor</th>
                    <th>Lokasi</th>
                    <th>Status Aset</th>
                    <th>Kode Kontrak</th>
                    <th>Status Kontrak</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inventarisList as $inv)
                <tr>
                    <td style="text-align: center;">
                        @if($inv->foto)
                            <img src="{{ asset('storage/' . $inv->foto) }}" alt="{{ $inv->nama_barang }}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px; border: 1px solid var(--gray-200);">
                        @else
                            <div style="width: 40px; height: 40px; border-radius: 8px; background: var(--brand-gray-light); color: var(--brand-gray); display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 16px;">
                                <i class="fas fa-image"></i>
                            </div>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('inventaris.show', $inv->kode_inventaris) }}" style="font-family: 'Courier New', monospace; font-weight: 700; color: var(--brand-orange); text-decoration: none;">
                            {{ $inv->kode_inventaris }}
                        </a>
                    </td>
                    <td>
                        <a href="{{ route('inventaris.show', $inv->kode_inventaris) }}" style="font-weight: 600; color: var(--gray-900); text-decoration: none;">
                            {{ $inv->nama_barang }}
                        </a>
                        @if($inv->merek || $inv->kondisi)
                        <div style="font-size: 11.5px; color: var(--brand-gray); margin-top: 2px;">
                            {{ $inv->merek ? $inv->merek . ' · ' : '' }}Kondisi: {{ $inv->kondisi ?: '-' }}
                        </div>
                        @endif
                    </td>
                    <td>{{ $inv->kategori ?: '-' }}</td>
                    <td>{{ $inv->vendor ?: '-' }}</td>
                    <td>{{ $inv->lokasi ?: '-' }}</td>
                    <td>@include('partials.badge-aset', ['status' => $inv->status_aset])</td>
                    <td>
                        @if($inv->kontrak)
                            <a href="{{ route('kontrak.show', $inv->kontrak->id) }}" style="font-family: 'Courier New', monospace; font-weight: 600; color: var(--gray-800); text-decoration: underline;">
                                {{ $inv->kode_kontrak }}
                            </a>
                        @else
                            <span class="code-badge code-empty">Tidak ada</span>
                        @endif
                    </td>
                    <td>@include('partials.badge-kontrak', ['status' => $inv->status_kontrak])</td>
                    <td>
                        <div class="action-group" style="justify-content: center;">
                            <a href="{{ route('inventaris.show', $inv->kode_inventaris) }}"
                               class="action-btn action-view" title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('inventaris.edit', $inv->kode_inventaris) }}"
                               class="action-btn action-edit" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST" action="{{ route('inventaris.destroy', $inv->kode_inventaris) }}" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="button" class="action-btn action-delete"
                                        onclick="confirmDelete(event, '{{ addslashes($inv->nama_barang) }}')"
                                        title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10">
                        <div style="padding: 48px 16px; text-align: center;">
                            <i class="fas fa-box-open" style="font-size: 40px; color: var(--brand-gray); margin-bottom: 10px; display: block;"></i>
                            <h3 style="font-size: 15px; font-weight: 700; color: var(--gray-900);">Tidak ada inventaris ditemukan</h3>
                            <p style="font-size: 13px; color: var(--brand-gray); margin-top: 4px;">Coba ubah filter pencarian atau tambahkan inventaris baru</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($inventarisList->hasPages())
    <div style="padding: 16px 24px; border-top: 1px solid var(--gray-100);">
        {{ $inventarisList->links('partials.pagination') }}
    </div>
    @endif
</div>

@include('partials.delete-modal')
@endsection
