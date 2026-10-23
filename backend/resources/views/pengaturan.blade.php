@extends('layouts.app')

@section('title', 'Pengaturan Sistem')
@section('page-title', 'Pengaturan')

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
        <h1><i class="fas fa-sliders-h"></i> Pengaturan Sistem & Informasi</h1>
        <p>Profil perusahaan dan konfigurasi aplikasi Monitoring Inventaris PT Banyan Koalindo Lestari</p>
    </div>
</div>

<div class="grid grid-2" style="max-width: 960px; gap: 24px;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-building" style="color: var(--brand-orange);"></i>
                Profil Perusahaan
            </div>
        </div>
        <div class="card-body">
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label class="form-label">Nama Perusahaan</label>
                    <input type="text" class="form-control" value="PT Banyan Koalindo Lestari" readonly style="background: var(--brand-gray-light);">
                </div>
                <div>
                    <label class="form-label">Sistem Informasi</label>
                    <input type="text" class="form-control" value="Sistem Informasi Monitoring Inventaris Sewa Beli" readonly style="background: var(--brand-gray-light);">
                </div>
                <div>
                    <label class="form-label">Versi Aplikasi</label>
                    <input type="text" class="form-control" value="v1.0.0 (Build 2026)" readonly style="background: var(--brand-gray-light);">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-info-circle" style="color: var(--brand-orange);"></i>
                Kriteria Status Kontrak Sewa Beli
            </div>
        </div>
        <div class="card-body">
            <div style="display: flex; flex-direction: column; gap: 14px; font-size: 13.5px; color: var(--gray-700);">
                <div style="padding: 12px 16px; background: var(--status-berjalan-bg); border-radius: 8px; border-left: 4px solid var(--status-berjalan);">
                    <strong style="color: var(--status-berjalan);">🟢 Sedang Berjalan</strong><br>
                    <span style="font-size: 12.5px; color: var(--gray-600);">Kontrak aktif dengan sisa masa berlaku lebih dari 30 hari.</span>
                </div>
                <div style="padding: 12px 16px; background: var(--status-akan-bg); border-radius: 8px; border-left: 4px solid var(--status-akan);">
                    <strong style="color: var(--status-akan);">🟡 Akan Berakhir</strong><br>
                    <span style="font-size: 12.5px; color: var(--gray-600);">Kontrak aktif yang akan berakhir dalam waktu &le; 30 hari (Perlu perhatian / perpanjangan).</span>
                </div>
                <div style="padding: 12px 16px; background: var(--status-habis-bg); border-radius: 8px; border-left: 4px solid var(--status-habis);">
                    <strong style="color: var(--status-habis);">🔴 Habis Kontrak</strong><br>
                    <span style="font-size: 12.5px; color: var(--gray-600);">Kontrak telah melewati batas tanggal berakhir sewa beli.</span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
