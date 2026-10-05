@extends('layouts.app')

@section('title', 'Detail Kontrak — ' . $kontrak->kode_kontrak)
@section('page-title', 'Detail Kontrak')

@section('content')

<div class="page-header" style="flex-wrap: wrap; gap: 12px;">
    <div class="page-header-left">
        <h1>Detail Kontrak Sewa Beli</h1>
        <p class="page-subtitle">{{ $kontrak->kode_kontrak }} — {{ $kontrak->vendor }}</p>
    </div>
    <div class="page-header-right" style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="{{ route('kontrak.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar
        </a>
        <a href="{{ route('kontrak.edit', $kontrak->id) }}" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Kontrak
        </a>
    </div>
</div>

<style>
.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px 24px;
}
.info-item {
    display: flex;
    flex-direction: column;
}
.info-label {
    font-size: 11px;
    font-weight: 700;
    color: var(--brand-gray);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 4px;
}
.info-value {
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-900);
    line-height: 1.4;
}
.info-value.highlight {
    font-size: 18px;
    font-weight: 700;
    color: var(--brand-orange);
    font-family: 'Courier New', monospace;
}
@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

{{-- Contract Information Card --}}
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div class="card-title">
            <i class="fas fa-file-contract" style="color: var(--brand-orange);"></i>
            Informasi Kontrak: {{ $kontrak->kode_kontrak }}
        </div>
        <div>
            @include('partials.badge-kontrak', ['status' => $kontrak->status_kontrak])
        </div>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Kode / Nomor Kontrak</span>
                <span class="info-value highlight">{{ $kontrak->kode_kontrak ?: '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Vendor / Supplier</span>
                <span class="info-value">{{ $kontrak->vendor ?: '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Status Kontrak</span>
                <div style="margin-top: 2px;">
                    @include('partials.badge-kontrak', ['status' => $kontrak->status_kontrak])
                </div>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Mulai</span>
                <span class="info-value">{{ $kontrak->tanggal_mulai ? $kontrak->tanggal_mulai->isoFormat('D MMMM Y') : '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Berakhir</span>
                <span class="info-value">{{ $kontrak->tanggal_berakhir ? $kontrak->tanggal_berakhir->isoFormat('D MMMM Y') : '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Durasi Kontrak</span>
                <span class="info-value">{{ $kontrak->durasi ?: '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Sisa Masa Kontrak</span>
                <span class="info-value" style="color: var(--brand-orange);">{{ $kontrak->sisa_kontrak ?: '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Biaya Sewa / Cicilan (per Bln)</span>
                <span class="info-value">Rp {{ number_format($kontrak->biaya_sewa, 0, ',', '.') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Total Biaya Kontrak</span>
                <span class="info-value" style="color: var(--status-ada);">
                    Rp {{ number_format($kontrak->total_biaya, 0, ',', '.') }}
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Jumlah Aset Terikat</span>
                <span class="info-value">{{ $kontrak->inventaris->count() }} Item Aset</span>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Dibuat</span>
                <span class="info-value">{{ $kontrak->created_at ? $kontrak->created_at->isoFormat('D MMMM Y, HH:mm') : '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Terakhir Diubah</span>
                <span class="info-value">{{ $kontrak->updated_at ? $kontrak->updated_at->isoFormat('D MMMM Y, HH:mm') : '-' }}</span>
            </div>
        </div>

        @if($kontrak->keterangan)
            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--gray-200);">
                <span class="info-label">Keterangan / Catatan Kontrak</span>
                <p style="font-size: 14px; color: var(--gray-700); margin-top: 4px; white-space: pre-line;">{{ $kontrak->keterangan }}</p>
            </div>
        @endif
    </div>
</div>

{{-- Table of Linked Inventaris --}}
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div class="card-title">
            <i class="fas fa-boxes-stacked" style="color: var(--brand-orange);"></i>
            Daftar Inventaris Terikat Kontrak Ini ({{ $kontrak->inventaris->count() }} Item)
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
                    <th>Lokasi</th>
                    <th>Kondisi</th>
                    <th>Status Aset</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kontrak->inventaris as $inv)
                    <tr>
                        <td style="text-align: center;">
                            @if($inv->foto)
                                <img src="{{ asset('storage/' . $inv->foto) }}" alt="{{ $inv->nama_barang }}" style="width: 36px; height: 36px; object-fit: cover; border-radius: 6px; border: 1px solid var(--gray-200);">
                            @else
                                <div style="width: 36px; height: 36px; border-radius: 6px; background: var(--brand-gray-light); color: var(--brand-gray); display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 14px;">
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
                        </td>
                        <td>{{ $inv->kategori ?: '-' }}</td>
                        <td>{{ $inv->lokasi ?: '-' }}</td>
                        <td>{{ $inv->kondisi ?: '-' }}</td>
                        <td>@include('partials.badge-aset', ['status' => $inv->status_aset])</td>
                        <td style="text-align: center;">
                            <a href="{{ route('inventaris.show', $inv->kode_inventaris) }}" class="btn btn-secondary btn-sm" title="Lihat Detail Aset">
                                <i class="fas fa-eye"></i> Lihat Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px 16px; color: var(--brand-gray);">
                            <i class="fas fa-box-open" style="font-size: 36px; margin-bottom: 10px; display: block; color: var(--brand-gray);"></i>
                            <p style="font-size: 14px; font-weight: 600; color: var(--gray-800);">Belum Ada Aset Terikat</p>
                            <p style="font-size: 12px; color: var(--brand-gray); margin-top: 2px;">Belum ada barang inventaris yang didaftarkan ke dalam kontrak ini.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
