@extends('layouts.app')

@section('title', 'Tambah Inventaris')
@section('page-title', 'Tambah Inventaris')

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Tambah Inventaris Baru</h1>
        <p class="page-subtitle">Isi informasi aset dan kontrak sewa beli</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('inventaris.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<form method="POST" action="{{ route('inventaris.store') }}" enctype="multipart/form-data">
    @csrf

    {{-- === INFORMASI INVENTARIS === --}}
    <div class="form-card">
        <div class="form-card-header">
            <i class="fas fa-box"></i>
            <h3>Informasi Inventaris</h3>
        </div>
        <div class="form-card-body">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="kode_inventaris">
                        Kode Inventaris <span class="required">*</span>
                    </label>
                    <input type="text" name="kode_inventaris" id="kode_inventaris"
                           value="{{ old('kode_inventaris') }}"
                           class="form-control @error('kode_inventaris') is-invalid @enderror"
                           placeholder="Contoh: INV-IT-001"
                           style="text-transform: uppercase;"
                           required>
                    <div class="form-hint">Format: INV-[KATEGORI]-[NOMOR], contoh: INV-IT-001</div>
                    @error('kode_inventaris')
                        <div class="form-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="nama_barang">
                        Nama Barang <span class="required">*</span>
                    </label>
                    <input type="text" name="nama_barang" id="nama_barang"
                           value="{{ old('nama_barang') }}"
                           class="form-control @error('nama_barang') is-invalid @enderror"
                           placeholder="Nama lengkap barang"
                           required>
                    @error('nama_barang')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="kategori">Kategori</label>
                    <input type="text" name="kategori" id="kategori"
                           value="{{ old('kategori') }}"
                           class="form-control"
                           placeholder="Contoh: Komputer & Laptop"
                           list="kategoriList">
                    <datalist id="kategoriList">
                        @foreach($kategoris as $kat)
                        <option value="{{ $kat }}">
                        @endforeach
                        <option value="Komputer & Laptop">
                        <option value="Printer & Scanner">
                        <option value="Jaringan">
                        <option value="Furnitur">
                        <option value="Elektronik">
                        <option value="Kendaraan">
                        <option value="Lainnya">
                    </datalist>
                </div>

                <div class="form-group">
                    <label class="form-label" for="merek">Merek / Brand</label>
                    <input type="text" name="merek" id="merek"
                           value="{{ old('merek') }}"
                           class="form-control"
                           placeholder="Contoh: Dell, HP, Lenovo, Canon">
                </div>

                <div class="form-group">
                    <label class="form-label" for="vendor">Vendor/Supplier</label>
                    <input type="text" name="vendor" id="vendor"
                           value="{{ old('vendor') }}"
                           class="form-control"
                           placeholder="Nama vendor atau supplier">
                </div>

                <div class="form-group">
                    <label class="form-label" for="lokasi">Lokasi</label>
                    <input type="text" name="lokasi" id="lokasi"
                           value="{{ old('lokasi') }}"
                           class="form-control"
                           placeholder="Contoh: Ruang IT, Server Room">
                </div>

                <div class="form-group">
                    <label class="form-label" for="kondisi">Kondisi Aset</label>
                    <select name="kondisi" id="kondisi" class="form-control">
                        <option value="">-- Pilih Kondisi --</option>
                        <option value="Baik" {{ old('kondisi') === 'Baik' ? 'selected' : '' }}>Baik</option>
                        <option value="Rusak Ringan" {{ old('kondisi') === 'Rusak Ringan' ? 'selected' : '' }}>Rusak Ringan</option>
                        <option value="Rusak Berat" {{ old('kondisi') === 'Rusak Berat' ? 'selected' : '' }}>Rusak Berat</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status_aset">
                        Status Aset <span class="required">*</span>
                    </label>
                    <select name="status_aset" id="status_aset" class="form-control" required>
                        <option value="">-- Pilih Status Aset --</option>
                        <option value="Ada" {{ old('status_aset') === 'Ada' ? 'selected' : '' }}>🟢 Ada — Tersedia secara fisik</option>
                        <option value="Hilang" {{ old('status_aset') === 'Hilang' ? 'selected' : '' }}>🔴 Hilang — Tidak ditemukan secara fisik</option>
                        <option value="Rusak" {{ old('status_aset') === 'Rusak' ? 'selected' : '' }}>🟠 Rusak — Ditemukan tetapi rusak</option>
                        <option value="Tidak Ditemukan" {{ old('status_aset') === 'Tidak Ditemukan' ? 'selected' : '' }}>⚪ Tidak Ditemukan — Belum diverifikasi</option>
                    </select>
                    @error('status_aset')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group form-col-full">
                    <label class="form-label" for="spesifikasi">Spesifikasi Detail</label>
                    <textarea name="spesifikasi" id="spesifikasi" class="form-control" rows="2"
                              placeholder="Contoh: Intel Core i7 12th Gen, RAM 16GB, SSD 512GB, Display 15.6 Inch FHD">{{ old('spesifikasi') }}</textarea>
                </div>

                <div class="form-group form-col-full">
                    <label class="form-label" for="foto">Foto Aset</label>
                    <input type="file" name="foto" id="foto" class="form-control @error('foto') is-invalid @enderror"
                           accept="image/jpeg,image/png">
                    <div class="form-hint">Format: JPG, JPEG, PNG (Maksimal 2 MB)</div>
                    @error('foto')
                        <div class="form-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group form-col-full">
                    <label class="form-label" for="keterangan">Keterangan</label>
                    <textarea name="keterangan" id="keterangan" class="form-control"
                              placeholder="Keterangan tambahan mengenai aset ini">{{ old('keterangan') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- === INFORMASI KONTRAK === --}}
    <div class="form-card">
        <div class="form-card-header">
            <i class="fas fa-file-contract"></i>
            <h3>Informasi Kontrak Sewa Beli</h3>
        </div>
        <div class="form-card-body">
            <div class="form-group" style="margin-bottom:24px;">
                <label class="form-label" style="font-size: 14px;">Memiliki Kontrak Sewa Beli?</label>
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" name="memiliki_kontrak" value="ya"
                               {{ old('memiliki_kontrak') === 'ya' ? 'checked' : '' }}
                               onchange="toggleContractFields()">
                        <i class="fas fa-circle-check" style="color: var(--status-ada); font-size: 16px;"></i>
                        <span>Ya, memiliki kontrak sewa beli</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="memiliki_kontrak" value="tidak"
                               {{ old('memiliki_kontrak', 'tidak') === 'tidak' ? 'checked' : '' }}
                               onchange="toggleContractFields()">
                        <i class="fas fa-circle-xmark" style="color: var(--gray-400); font-size: 16px;"></i>
                        <span>Tidak memiliki kontrak (aset mandiri)</span>
                    </label>
                </div>
            </div>

            {{-- Contract fields (shown conditionally) --}}
            <div id="contractSection" style="display:none;">
                <div class="info-banner blue" style="margin-bottom: 22px;">
                    <i class="fas fa-circle-info"></i>
                    <div>
                        <strong style="font-size: 13.5px; display: block; margin-bottom: 2px;">Pengaturan Kontrak Sewa Beli</strong>
                        Pilih kontrak yang sudah ada atau isi formulir pembuatan kontrak baru di bawah.
                        Status dan sisa masa kontrak akan dihitung otomatis oleh sistem.
                    </div>
                </div>

                {{-- Source: existing or new --}}
                <div class="form-group" style="margin-bottom: 22px;">
                    <label class="form-label">Sumber Data Kontrak</label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="kontrak_source" value="existing"
                                   {{ old('kontrak_source', 'existing') === 'existing' ? 'checked' : '' }}
                                   onchange="toggleContractSource()">
                            <i class="fas fa-folder-open" style="color: var(--brand-orange);"></i>
                            <span>Pilih kontrak yang sudah terdaftar</span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="kontrak_source" value="new"
                                   {{ old('kontrak_source') === 'new' ? 'checked' : '' }}
                                   onchange="toggleContractSource()">
                            <i class="fas fa-plus-circle" style="color: var(--brand-orange);"></i>
                            <span>Buat kontrak baru langsung</span>
                        </label>
                    </div>
                </div>

                {{-- Existing Kontrak Selection --}}
                <div id="existingKontrakSection">
                    <div class="form-group">
                        <label class="form-label" for="kontrak_id">Pilih Kontrak</label>
                        <select name="kontrak_id" id="kontrak_id" class="form-control">
                            <option value="">-- Pilih Kontrak --</option>
                            @foreach($kontrakList as $kontrak)
                            <option value="{{ $kontrak->id }}"
                                    {{ old('kontrak_id') == $kontrak->id ? 'selected' : '' }}>
                                {{ $kontrak->kode_kontrak }} — {{ $kontrak->vendor }}
                                ({{ $kontrak->tanggal_mulai->format('d/m/Y') }} s/d {{ $kontrak->tanggal_berakhir->format('d/m/Y') }})
                                — {{ $kontrak->status_kontrak }}
                            </option>
                            @endforeach
                        </select>
                        <div class="form-hint">Jika kontrak belum ada, pilih "Buat kontrak baru" di atas</div>
                    </div>
                </div>

                {{-- New Kontrak Fields --}}
                <div id="newKontrakSection" style="display:none;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="kode_kontrak_baru">
                                Nomor/Kode Kontrak <span class="required">*</span>
                            </label>
                            <input type="text" name="kode_kontrak_baru" id="kode_kontrak_baru"
                                   value="{{ old('kode_kontrak_baru') }}"
                                   class="form-control"
                                   placeholder="Contoh: KON-2026-001"
                                   style="text-transform: uppercase;">
                            <div class="form-hint">Format: KON-[TAHUN]-[NOMOR]</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="vendor_kontrak">Vendor Kontrak</label>
                            <input type="text" name="vendor_kontrak" id="vendor_kontrak"
                                   value="{{ old('vendor_kontrak') }}"
                                   class="form-control"
                                   placeholder="Nama vendor kontrak">
                            <div class="form-hint">Biarkan kosong untuk menggunakan vendor aset</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="tanggal_mulai">
                                Tanggal Mulai <span class="required">*</span>
                            </label>
                            <input type="date" name="tanggal_mulai" id="tanggal_mulai"
                                   value="{{ old('tanggal_mulai') }}"
                                   class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="tanggal_berakhir">
                                Tanggal Berakhir <span class="required">*</span>
                            </label>
                            <input type="date" name="tanggal_berakhir" id="tanggal_berakhir"
                                   value="{{ old('tanggal_berakhir') }}"
                                   class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="durasi_kontrak">Durasi Kontrak</label>
                            <input type="text" name="durasi_kontrak" id="durasi_kontrak"
                                   value="{{ old('durasi_kontrak') }}"
                                   class="form-control"
                                   placeholder="Otomatis dari tanggal (atau isi manual)"
                                   readonly>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="biaya_sewa">Biaya Sewa/Cicilan (Rp)</label>
                            <input type="number" name="biaya_sewa" id="biaya_sewa"
                                   value="{{ old('biaya_sewa', 0) }}"
                                   class="form-control"
                                   placeholder="0"
                                   min="0" step="1000">
                        </div>

                        <div class="form-group form-col-full">
                            <label class="form-label" for="keterangan_kontrak">Keterangan Kontrak</label>
                            <textarea name="keterangan_kontrak" id="keterangan_kontrak" class="form-control"
                                      placeholder="Keterangan mengenai kontrak ini">{{ old('keterangan_kontrak') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <a href="{{ route('inventaris.index') }}" class="btn btn-secondary">
                <i class="fas fa-xmark"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Inventaris
            </button>
        </div>
    </div>
</form>

@endsection
