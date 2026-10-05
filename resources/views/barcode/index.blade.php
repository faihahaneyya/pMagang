@extends('layouts.app')

@section('title', 'Cetak Barcode / QR Code')
@section('page-title', 'Cetak Barcode')

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
        <h1><i class="fas fa-qrcode"></i> Cetak Label & Export QR Code</h1>
        <p>Kelola dan cetak label QR Code aset inventaris PT Banyan Koalindo Lestari</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="printSelectedBarcodes()">
            <i class="fas fa-print"></i> Cetak QR Code Terpilih
        </button>
    </div>
</div>

<div class="card">
    <div class="table-header">
        <div class="table-title">
            <i class="fas fa-barcode" style="color: var(--brand-orange);"></i>
            <span>Daftar Barcode Aset</span>
            <span class="title-badge">{{ is_object($inventarisList) && method_exists($inventarisList, 'total') ? $inventarisList->total() : count($inventarisList) }} Item</span>
        </div>
        <div class="table-actions">
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('selectAll').click()">
                <i class="fas fa-check-square"></i> Pilih Semua
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAllBarcodes(this)" style="cursor: pointer; width: 16px; height: 16px;">
                    </th>
                    <th>Kode Inventaris</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Lokasi</th>
                    <th style="text-align: center;">Preview QR Code</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inventarisList as $item)
                    <tr>
                        <td style="text-align: center;">
                            <input type="checkbox" class="barcode-select-item" value="{{ $item->kode_inventaris }}" style="cursor: pointer; width: 16px; height: 16px;">
                        </td>
                        <td>
                            <a href="{{ route('inventaris.show', $item->kode_inventaris) }}" style="font-family: 'Courier New', monospace; font-weight: 700; color: var(--brand-orange); text-decoration: none;">
                                {{ $item->kode_inventaris }}
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('inventaris.show', $item->kode_inventaris) }}" style="font-weight: 600; color: var(--gray-900); text-decoration: none;">
                                {{ $item->nama_barang }}
                            </a>
                        </td>
                        <td>{{ $item->kategori ?: '-' }}</td>
                        <td>{{ $item->lokasi ?: '-' }}</td>
                        <td style="text-align: center; padding: 10px;">
                            <div style="display: inline-block; background: #fff; padding: 6px; border: 1.5px dashed var(--brand-orange-border); border-radius: 8px;">
                                @if(class_exists('SimpleSoftwareIO\QrCode\Facades\QrCode'))
                                    {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(50)->generate(route('inventaris.show', $item->kode_inventaris)) !!}
                                @else
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=50x50&data={{ urlencode(route('inventaris.show', $item->kode_inventaris)) }}" alt="QR" style="width: 50px; height: 50px;">
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="action-group" style="justify-content: center;">
                                <a href="{{ route('barcode.download', $item->kode_inventaris) }}" class="action-btn action-download" title="Unduh PNG">
                                    <i class="fas fa-download"></i>
                                </a>
                                <a href="{{ route('barcode.print', $item->kode_inventaris) }}" target="_blank" class="action-btn action-view" title="Cetak Label Single">
                                    <i class="fas fa-print"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 48px 16px; color: var(--brand-gray);">
                            <i class="fas fa-qrcode" style="font-size: 36px; margin-bottom: 10px; display: block;"></i>
                            <p style="font-size: 14px; font-weight: 600; color: var(--gray-800);">Belum Ada Data Inventaris</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($inventarisList, 'hasPages') && $inventarisList->hasPages())
        <div style="padding: 16px 24px; border-top: 1px solid var(--gray-100);">
            {{ $inventarisList->links('partials.pagination') }}
        </div>
    @endif
</div>

<script>
function toggleSelectAllBarcodes(source) {
    const checkboxes = document.querySelectorAll('.barcode-select-item');
    checkboxes.forEach(cb => cb.checked = source.checked);
}

function printSelectedBarcodes() {
    const selected = Array.from(document.querySelectorAll('.barcode-select-item:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        alert('Silakan pilih setidaknya satu barcode untuk dicetak.');
        return;
    }
    
    const printUrl = "{{ route('barcode.print-all') }}?kodes=" + selected.join(',');
    window.open(printUrl, '_blank');
}
</script>

@endsection
