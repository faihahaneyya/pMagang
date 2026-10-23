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
                        <a href="{{ route('barcode.print', $inv->kode_inventaris) }}" target="_blank"
                           class="action-btn action-view" title="Cetak Label QR Code">
                            <i class="fas fa-qrcode"></i>
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
