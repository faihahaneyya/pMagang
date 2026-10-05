<div class="table-header">
    <div class="table-title">
        <i class="fas fa-list-ul" style="color: var(--brand-orange); font-size: 16px;"></i>
        <span>{{ $listTitle }}</span>
        <span class="title-badge">{{ count($listInventaris) }} item</span>
    </div>
    <div class="table-actions">
        @if($filter !== 'terbaru')
        <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-xmark"></i> Reset Filter
        </a>
        @endif
        <a href="{{ route('inventaris.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Tambah Aset
        </a>
        <a href="{{ route('inventaris.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-right"></i> Kelola Inventaris
        </a>
    </div>
</div>

<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th style="width: 50px; text-align: center;">Foto</th>
                <th>Kode Inventaris</th>
                <th>Nama Barang</th>
                <th>Lokasi Penempatan</th>
                <th>Kode Kontrak</th>
                <th>Status Aset</th>
                <th>Status Kontrak</th>
                <th>Sisa Masa Kontrak</th>
                <th style="text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($listInventaris as $inv)
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
                    @if($inv->merek || $inv->kategori)
                    <div style="font-size: 11.5px; color: var(--brand-gray); margin-top: 2px;">
                        {{ $inv->merek ?: $inv->kategori }}
                    </div>
                    @endif
                </td>
                <td>{{ $inv->lokasi ?: '-' }}</td>
                <td>
                    @if($inv->kontrak)
                        <a href="{{ route('kontrak.show', $inv->kontrak->id) }}" style="font-family: 'Courier New', monospace; font-weight: 600; color: var(--gray-800); text-decoration: underline;">
                            {{ $inv->kode_kontrak }}
                        </a>
                    @else
                        <span class="code-badge code-empty">Tidak ada</span>
                    @endif
                </td>
                <td>@include('partials.badge-aset', ['status' => $inv->status_aset])</td>
                <td>@include('partials.badge-kontrak', ['status' => $inv->status_kontrak])</td>
                <td>
                    @if($inv->kontrak)
                        @php
                            $sisa = $inv->sisa_kontrak;
                            $kelas = str_contains($sisa, 'lalu') ? 'expired' : (str_contains($sisa, 'hari lagi') && !str_contains($sisa, 'bulan') ? 'urgent' : '');
                        @endphp
                        <span class="sisa-badge {{ $kelas }}">
                            <i class="fas fa-calendar-alt"></i> {{ $sisa }}
                        </span>
                    @else
                        <span style="color: var(--brand-gray);">-</span>
                    @endif
                </td>
                <td>
                    <div class="action-group" style="justify-content: center;">
                        <a href="{{ route('inventaris.show', $inv->kode_inventaris) }}"
                           class="action-btn action-view" title="Lihat Detail">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('inventaris.edit', $inv->kode_inventaris) }}"
                           class="action-btn action-edit" title="Edit Aset">
                            <i class="fas fa-pen"></i>
                        </a>
                        <form method="POST" action="{{ route('inventaris.destroy', $inv->kode_inventaris) }}" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="button" class="action-btn action-delete"
                                    onclick="confirmDelete(event, '{{ addslashes($inv->nama_barang) }}')"
                                    title="Hapus Aset">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9">
                    <div class="table-empty" style="padding: 40px 16px; text-align: center;">
                        <i class="fas fa-inbox" style="font-size: 36px; color: var(--brand-gray); margin-bottom: 8px;"></i>
                        <p style="font-size: 14px; font-weight: 600; color: var(--gray-800);">Tidak ada data inventaris untuk filter ini</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
