@extends('layouts.app')

@section('title', 'Edit Kontrak — ' . $kontrak->kode_kontrak)
@section('page-title', 'Edit Kontrak')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Kontrak Sewa Beli</h1>
        <p class="page-subtitle">Perbarui data kontrak {{ $kontrak->kode_kontrak }}</p>
    </div>
    <div class="page-header-right">
        <a href="{{ route('kontrak.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Batal & Kembali
        </a>
    </div>
</div>

<form action="{{ route('kontrak.update', $kontrak->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-edit" style="color: var(--navy-500);"></i>
                Form Edit Kontrak
            </div>
        </div>
        <div class="card-body">
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label required">Kode Kontrak</label>
                    <input type="text" name="kode_kontrak" class="form-control" value="{{ old('kode_kontrak', $kontrak->kode_kontrak) }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label required">Vendor / Supplier</label>
                    <input type="text" name="vendor" class="form-control" value="{{ old('vendor', $kontrak->vendor) }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai', $kontrak->tanggal_mulai ? $kontrak->tanggal_mulai->format('Y-m-d') : '') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Berakhir</label>
                    <input type="date" name="tanggal_berakhir" class="form-control" value="{{ old('tanggal_berakhir', $kontrak->tanggal_berakhir ? $kontrak->tanggal_berakhir->format('Y-m-d') : '') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Durasi Kontrak (Bulan)</label>
                    <input type="number" name="durasi" class="form-control" value="{{ old('durasi', $kontrak->durasi) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Biaya Sewa (Rp)</label>
                    <input type="number" name="biaya_sewa" class="form-control" value="{{ old('biaya_sewa', $kontrak->biaya_sewa) }}">
                </div>
            </div>

            <div class="form-group" style="margin-top: 16px;">
                <label class="form-label">Keterangan / Catatan Kontrak</label>
                <textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan', $kontrak->keterangan) }}</textarea>
            </div>

            <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
                <a href="{{ route('kontrak.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-navy">
                    <i class="fas fa-save"></i> Perbarui Kontrak
                </button>
            </div>
        </div>
    </div>
</form>

@endsection
