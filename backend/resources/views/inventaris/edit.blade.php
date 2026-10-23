@extends('layouts.app')

@section('title', 'Edit Inventaris — ' . $inventaris->kode_inventaris)
@section('page-title', 'Edit Inventaris')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Inventaris</h1>
        <p class="page-subtitle">{{ $inventaris->kode_inventaris }} — {{ $inventaris->nama_barang }}</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('inventaris.show', $inventaris->kode_inventaris) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<form method="POST" action="{{ route('inventaris.update', $inventaris->kode_inventaris) }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    {{-- Informasi Inventaris --}}
    <div class="form-card">
        <div class="form-card-header">
            <i class="fas fa-box"></i>
            <h3>Informasi Inventaris</h3>
        </div>
        <div class="form-card-body">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Kode Inventaris <span class="required">*</span></label>
                    <input type="text" name="kode_inventaris"
                           value="{{ old('kode_inventaris', $inventaris->kode_inventaris) }}"
                           class="form-control" style="text-transform: uppercase;" required>
                    @error('kode_inventaris')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Barang <span class="required">*</span></label>
                    <input type="text" name="nama_barang"
                           value="{{ old('nama_barang', $inventaris->nama_barang) }}"
                           class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Kategori</label>
                    <input type="text" name="kategori"
                           value="{{ old('kategori', $inventaris->kategori) }}"
                           class="form-control" list="kategoriList">
                    <datalist id="kategoriList">
                        @foreach($kategoris as $kat)
                        <option value="{{ $kat }}">
                        @endforeach
                    </datalist>
                </div>

                <div class="form-group">
                    <label class="form-label">Merek / Brand</label>
                    <input type="text" name="merek"
                           value="{{ old('merek', $inventaris->merek) }}"
                           class="form-control" placeholder="Contoh: Dell, HP, Lenovo, Canon">
                </div>

                <div class="form-group">
                    <label class="form-label">Vendor/Supplier</label>
                    <input type="text" name="vendor"
                           value="{{ old('vendor', $inventaris->vendor) }}"
                           class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="lokasi"
                           value="{{ old('lokasi', $inventaris->lokasi) }}"
                           class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Kondisi Aset</label>
                    <select name="kondisi" class="form-control">
                        <option value="">-- Pilih --</option>
                        @foreach(['Baik', 'Rusak Ringan', 'Rusak Berat'] as $k)
                        <option value="{{ $k }}" {{ old('kondisi', $inventaris->kondisi) === $k ? 'selected' : '' }}>{{ $k }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Status Aset <span class="required">*</span></label>
                    <select name="status_aset" class="form-control" required>
                        @foreach(['Ada', 'Hilang', 'Rusak', 'Tidak Ditemukan'] as $s)
                        <option value="{{ $s }}" {{ old('status_aset', $inventaris->status_aset) === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group form-col-full">
                    <label class="form-label">Spesifikasi Detail</label>
                    <textarea name="spesifikasi" class="form-control" rows="2" placeholder="Spesifikasi teknis barang">{{ old('spesifikasi', $inventaris->spesifikasi) }}</textarea>
                </div>

                <div class="form-group form-col-full">
                    <label class="form-label">Foto Aset</label>
                    @if($inventaris->foto)
                    <div style="margin-bottom: 8px;">
                        <img src="{{ asset('storage/' . $inventaris->foto) }}" alt="Foto Barang"
                             style="height: 100px; border-radius: 8px; border: 1px solid var(--navy-100); object-fit: cover;">
                    </div>
                    @endif
                    <input type="file" name="foto" class="form-control @error('foto') is-invalid @enderror" accept="image/jpeg,image/png">
                    <div class="form-hint">Format: JPG, JPEG, PNG (Maksimal 2 MB). Kosongkan jika tidak ingin mengubah foto.</div>
                    @error('foto')
                        <div class="form-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group form-col-full">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control">{{ old('keterangan', $inventaris->keterangan) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Informasi Kontrak --}}
    <div class="form-card">
        <div class="form-card-header">
            <i class="fas fa-file-contract"></i>
            <h3>Informasi Kontrak Sewa Beli</h3>
        </div>
        <div class="form-card-body">
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Memiliki Kontrak Sewa Beli?</label>
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" name="memiliki_kontrak" value="ya"
                               {{ old('memiliki_kontrak', $inventaris->kontrak_id ? 'ya' : 'tidak') === 'ya' ? 'checked' : '' }}
                               onchange="toggleContractFields()">
                        <i class="fas fa-circle-check" style="color: var(--status-ada);"></i> Ya
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="memiliki_kontrak" value="tidak"
                               {{ old('memiliki_kontrak', $inventaris->kontrak_id ? 'ya' : 'tidak') === 'tidak' ? 'checked' : '' }}
                               onchange="toggleContractFields()">
                        <i class="fas fa-circle-xmark" style="color: var(--gray-400);"></i> Tidak
                    </label>
                </div>
            </div>

            <div id="contractSection" style="{{ ($inventaris->kontrak_id || old('memiliki_kontrak') === 'ya') ? '' : 'display:none;' }}">
                <div class="form-group">
                    <label class="form-label">Pilih Kontrak</label>
                    <select name="kontrak_id" id="kontrak_id" class="form-control">
                        <option value="">-- Tidak Ada Kontrak --</option>
                        @foreach($kontrakList as $kontrak)
                        <option value="{{ $kontrak->id }}"
                                {{ old('kontrak_id', $inventaris->kontrak_id) == $kontrak->id ? 'selected' : '' }}>
                            {{ $kontrak->kode_kontrak }} — {{ $kontrak->vendor }}
                            ({{ $kontrak->tanggal_mulai->format('d/m/Y') }} s/d {{ $kontrak->tanggal_berakhir->format('d/m/Y') }})
                            — {{ $kontrak->status_kontrak }}
                        </option>
                        @endforeach
                    </select>
                    <div class="form-hint">
                        <a href="{{ route('kontrak.create') }}" target="_blank" style="color: var(--navy-500);">
                            + Tambah kontrak baru
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('inventaris.show', $inventaris->kode_inventaris) }}" class="btn btn-secondary">
                <i class="fas fa-xmark"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
        </div>
    </div>
</form>

@endsection
