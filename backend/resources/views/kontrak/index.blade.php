@extends('layouts.app')

@section('title', 'Kontrak Sewa Beli')
@section('page-title', 'Kontrak Sewa Beli')

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

{{-- Page Hero --}}
<div class="page-hero">
    <div>
        <h1><i class="fas fa-file-contract"></i> Daftar Kontrak Sewa Beli</h1>
        <p>Kelola seluruh kontrak sewa beli aset dan pengelompokan inventaris PT Banyan Koalindo Lestari</p>
    </div>
    <div>
        <a href="{{ route('kontrak.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Kontrak Baru
        </a>
    </div>
</div>

<div class="card">
    <div class="table-header">
        <div class="table-title">
            <i class="fas fa-file-signature" style="color: var(--brand-orange);"></i>
            <span>Master Kontrak</span>
            <span class="title-badge">{{ is_object($kontrakList) && method_exists($kontrakList, 'total') ? $kontrakList->total() : count($kontrakList) }} Total</span>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Kode Kontrak</th>
                    <th>Vendor / Supplier</th>
                    <th>Periode Kontrak</th>
                    <th>Durasi</th>
                    <th>Biaya Sewa / Bln</th>
                    <th>Jumlah Aset</th>
                    <th>Status Kontrak</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kontrakList as $kontrak)
                    <tr>
                        <td>
                            <a href="{{ route('kontrak.show', $kontrak->id) }}" style="font-family: 'Courier New', monospace; font-weight: 700; color: var(--brand-orange); text-decoration: none;">
                                {{ $kontrak->kode_kontrak }}
                            </a>
                        </td>
                        <td>
                            <strong style="color: var(--gray-900);">{{ $kontrak->vendor }}</strong>
                        </td>
                        <td>
                            {{ $kontrak->tanggal_mulai ? $kontrak->tanggal_mulai->format('d/m/Y') : '-' }} s/d {{ $kontrak->tanggal_berakhir ? $kontrak->tanggal_berakhir->format('d/m/Y') : '-' }}
                        </td>
                        <td>{{ $kontrak->durasi ?? '-' }}</td>
                        <td>Rp {{ number_format($kontrak->biaya_sewa, 0, ',', '.') }}</td>
                        <td>
                            <span class="title-badge">{{ $kontrak->inventaris_count ?? $kontrak->inventaris()->count() }} Aset</span>
                        </td>
                        <td>
                            @include('partials.badge-kontrak', ['status' => $kontrak->status_kontrak])
                        </td>
                        <td>
                            <div class="action-group" style="justify-content: center;">
                                <a href="{{ route('kontrak.show', $kontrak->id) }}" class="action-btn action-view" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('kontrak.edit', $kontrak->id) }}" class="action-btn action-edit" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form action="{{ route('kontrak.destroy', $kontrak->id) }}" method="POST" style="display:inline;" onsubmit="confirmDelete(event, 'Kontrak {{ $kontrak->kode_kontrak }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn action-delete" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 48px 16px; color: var(--brand-gray);">
                            <i class="fas fa-file-excel" style="font-size: 36px; margin-bottom: 10px; display: block;"></i>
                            <p style="font-size: 14px; font-weight: 600; color: var(--gray-800);">Belum Ada Data Kontrak</p>
                            <p style="font-size: 12px; color: var(--brand-gray); margin-top: 2px;">Klik tombol Tambah Kontrak Baru di atas untuk mendaftarkan kontrak sewa beli.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($kontrakList, 'hasPages') && $kontrakList->hasPages())
        <div style="padding: 16px 24px; border-top: 1px solid var(--gray-100);">
            {{ $kontrakList->links('partials.pagination') }}
        </div>
    @endif
</div>

@include('partials.delete-modal')

@endsection
