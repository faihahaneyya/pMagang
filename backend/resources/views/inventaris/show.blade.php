@extends('layouts.app')

@section('title', 'Detail Inventaris — ' . $inventaris->kode_inventaris)
@section('page-title', 'Detail Inventaris')

@section('content')

<div class="page-header" style="flex-wrap: wrap; gap: 12px;">
    <div class="page-header-left">
        <h1>Detail Inventaris</h1>
        <p class="page-subtitle">{{ $inventaris->kode_inventaris }} — {{ $inventaris->nama_barang }}</p>
    </div>
    <div class="page-header-right" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
        <a href="{{ route('inventaris.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar
        </a>

        @if(auth()->check())
            <a href="{{ route('inventaris.edit', $inventaris->kode_inventaris) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Aset
            </a>

            <form action="{{ route('inventaris.destroy', $inventaris->kode_inventaris) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus inventaris ini?');" style="display: inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </form>
        @endif
    </div>
</div>

<style>
.detail-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    align-items: start;
}
.asset-photo-box {
    width: 100%;
    max-height: 340px;
    border-radius: 12px;
    overflow: hidden;
    background: var(--brand-gray-light);
    border: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
}
.asset-photo-box img {
    width: 100%;
    height: 100%;
    max-height: 340px;
    object-fit: contain;
}
.asset-photo-placeholder {
    padding: 48px 24px;
    text-align: center;
    color: var(--brand-gray);
}
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
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
    .detail-layout {
        grid-template-columns: 1fr;
    }
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="detail-layout">
    {{-- Main Left Column: Asset Info & Contract Info --}}
    <div style="display: flex; flex-direction: column; gap: 24px;">

        {{-- Card 1: Informasi Aset Lengkap --}}
        <div class="card">
            <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div class="card-title">
                    <i class="fas fa-box" style="color: var(--brand-orange);"></i>
                    Informasi Aset Lengkap
                </div>
                <div>
                    @include('partials.badge-aset', ['status' => $inventaris->status_aset])
                </div>
            </div>
            <div class="card-body">
                {{-- Foto Aset / Placeholder --}}
                <div class="asset-photo-box">
                    @if($inventaris->foto)
                        <img src="{{ asset('storage/' . $inventaris->foto) }}" alt="Foto {{ $inventaris->nama_barang }}">
                    @else
                        <div class="asset-photo-placeholder">
                            <i class="fas fa-image" style="font-size: 52px; margin-bottom: 12px; display: block; color: var(--brand-gray);"></i>
                            <span style="font-size: 13px; font-weight: 600;">Foto Aset Belum Tersedia</span>
                        </div>
                    @endif
                </div>

                {{-- Detail Fields Grid --}}
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Kode Inventaris</span>
                        <span class="info-value highlight">{{ $inventaris->kode_inventaris ?: '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Nama Barang</span>
                        <span class="info-value">{{ $inventaris->nama_barang ?: '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Kategori</span>
                        <span class="info-value">{{ $inventaris->kategori ?: '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Merek / Brand</span>
                        <span class="info-value">{{ $inventaris->merek ?: '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Vendor / Supplier</span>
                        <span class="info-value">{{ $inventaris->vendor ?: '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Lokasi Penempatan</span>
                        <span class="info-value">{{ $inventaris->lokasi ?: '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Kondisi Aset</span>
                        <span class="info-value">{{ $inventaris->kondisi ?: '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status Aset</span>
                        <div style="margin-top: 2px;">
                            @include('partials.badge-aset', ['status' => $inventaris->status_aset])
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tanggal Dibuat</span>
                        <span class="info-value">{{ $inventaris->created_at ? $inventaris->created_at->isoFormat('D MMMM Y, HH:mm') : '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Terakhir Diubah</span>
                        <span class="info-value">{{ $inventaris->updated_at ? $inventaris->updated_at->isoFormat('D MMMM Y, HH:mm') : '-' }}</span>
                    </div>
                </div>

                @if($inventaris->spesifikasi)
                    <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--gray-200);">
                        <span class="info-label">Spesifikasi Detail</span>
                        <p style="font-size: 14px; color: var(--gray-800); margin-top: 4px; white-space: pre-line; line-height: 1.5;">{{ $inventaris->spesifikasi }}</p>
                    </div>
                @endif

                @if($inventaris->keterangan)
                    <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--gray-200);">
                        <span class="info-label">Catatan / Keterangan</span>
                        <p style="font-size: 14px; color: var(--gray-700); margin-top: 4px; white-space: pre-line;">{{ $inventaris->keterangan }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Card 2: Informasi Kontrak Sewa Beli --}}
        <div class="card">
            <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div class="card-title">
                    <i class="fas fa-file-contract" style="color: var(--brand-orange);"></i>
                    Informasi Kontrak Sewa Beli
                </div>
                <div>
                    @if($inventaris->kontrak)
                        @include('partials.badge-kontrak', ['status' => $inventaris->status_kontrak])
                    @else
                        <span class="badge badge-no-contract">Tidak Berkontrak</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($inventaris->kontrak)
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Nomor / Kode Kontrak</span>
                            <span class="info-value highlight">{{ $inventaris->kontrak->kode_kontrak }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Vendor Kontrak</span>
                            <span class="info-value">{{ $inventaris->kontrak->vendor ?: '-' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tanggal Mulai</span>
                            <span class="info-value">{{ $inventaris->kontrak->tanggal_mulai ? $inventaris->kontrak->tanggal_mulai->isoFormat('D MMMM Y') : '-' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tanggal Berakhir</span>
                            <span class="info-value">{{ $inventaris->kontrak->tanggal_berakhir ? $inventaris->kontrak->tanggal_berakhir->isoFormat('D MMMM Y') : '-' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Durasi Kontrak</span>
                            <span class="info-value">{{ $inventaris->kontrak->durasi ?: '-' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Sisa Masa Kontrak</span>
                            <span class="info-value" style="color: var(--brand-orange);">{{ $inventaris->kontrak->sisa_kontrak ?: '-' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Biaya Sewa / Cicilan (per Bln)</span>
                            <span class="info-value">Rp {{ number_format($inventaris->kontrak->biaya_sewa, 0, ',', '.') }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Biaya Kontrak</span>
                            <span class="info-value" style="color: var(--status-ada);">
                                Rp {{ number_format($inventaris->kontrak->total_biaya, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    @if($inventaris->kontrak->keterangan)
                        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--gray-200);">
                            <span class="info-label">Keterangan Kontrak</span>
                            <p style="font-size: 14px; color: var(--gray-700); margin-top: 4px;">{{ $inventaris->kontrak->keterangan }}</p>
                        </div>
                    @endif
                @else
                    <div style="text-align: center; padding: 32px 16px; color: var(--brand-gray);">
                        <i class="fas fa-file-circle-xmark" style="font-size: 40px; margin-bottom: 10px; display: block; color: var(--brand-gray);"></i>
                        <p style="font-size: 15px; font-weight: 600; color: var(--gray-800);">Tidak Memiliki Kontrak</p>
                        <p style="font-size: 13px; color: var(--brand-gray); margin-top: 4px;">Aset ini terdaftar sebagai inventaris mandiri dan tidak terikat kontrak sewa beli.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- Right Column: Barcode 1D & Quick Actions --}}
    <div style="display: flex; flex-direction: column; gap: 24px;">

        {{-- Card 3: Label QR Code (2D) --}}
        <div class="card" style="text-align: center;">
            <div class="card-header">
                <div class="card-title" style="justify-content: center; width: 100%;">
                    <i class="fas fa-qrcode" style="color: var(--brand-orange);"></i>
                    Label QR Code (2D)
                </div>
            </div>
            <div class="card-body" style="display: flex; flex-direction: column; align-items: center; gap: 16px;">
                <div style="background: #ffffff; border: 2px dashed var(--brand-orange-border); border-radius: 12px; padding: 18px 24px; display: inline-flex; flex-direction: column; align-items: center; box-shadow: var(--shadow-sm); width: 100%; max-width: 280px;">
                    <div style="width: 140px; height: 140px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        {!! $inventaris->qr_code_svg !!}
                    </div>
                    <div style="font-family: 'Courier New', monospace; font-size: 16px; font-weight: 800; color: var(--gray-900); margin-top: 8px; letter-spacing: 1px;">
                        {{ $inventaris->kode_inventaris }}
                    </div>
                </div>
                <div>
                    <span class="info-label">Nama Barang</span>
                    <div style="font-size: 14px; font-weight: 700; color: var(--gray-800); margin-top: 2px;">
                        {{ $inventaris->nama_barang }}
                    </div>
                </div>
                <p style="font-size: 12.5px; color: var(--brand-gray); max-width: 240px; line-height: 1.4;">
                    Format QR Code (2D) dapat dipindai langsung menggunakan kamera HP untuk membuka rincian aset inventaris resmi.
                </p>
                <div style="display: flex; gap: 10px; width: 100%;">
                    <a href="{{ route('barcode.download', $inventaris->kode_inventaris) }}" class="btn btn-secondary" style="flex: 1; justify-content: center;">
                        <i class="fas fa-download"></i> Unduh PNG
                    </a>
                    <a href="{{ route('barcode.print', $inventaris->kode_inventaris) }}" target="_blank" class="btn btn-primary" style="flex: 1; justify-content: center;">
                        <i class="fas fa-print"></i> Cetak QR Code
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection
