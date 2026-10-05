<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label Barcode Terpilih</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #fff; color: #1F2937; padding: 20px; }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }
        .label-card {
            border: 1.5px solid #F05D24;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            background: #fff;
            page-break-inside: avoid;
        }
        .company-header {
            font-size: 10px;
            font-weight: 700;
            color: #F05D24;
            text-transform: uppercase;
        }
        .system-title { font-size: 9px; color: #7E7E7E; margin-bottom: 8px; }
        .qr-wrapper {
            background: #fff;
            padding: 6px;
            border: 1px dashed #F8C3B0;
            border-radius: 6px;
            display: inline-block;
            margin-bottom: 8px;
        }
        .qr-wrapper img { width: 130px; height: 130px; display: block; }
        .item-code { font-family: monospace; font-size: 14px; font-weight: 700; color: #1A1D24; }
        .item-name { font-size: 12px; font-weight: 600; color: #374151; margin-top: 2px; }
        .item-meta { font-size: 10px; color: #7E7E7E; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            .label-card { border-color: #000; }
            .company-header { color: #000; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
        <h2>Cetak Multi Label QR Code</h2>
        <button onclick="window.print()" style="background: #F05D24; color: #fff; border: none; padding: 10px 24px; font-weight: 600; border-radius: 6px; cursor: pointer;">
            🖨️ Cetak Semua Label ({{ count($items) }})
        </button>
    </div>

    <div class="grid-container">
        @foreach($items as $item)
            <div class="label-card">
                <div class="company-header">PT Banyan Koalindo Lestari</div>
                <div class="system-title">Inventaris Sewa Beli</div>
                <div class="qr-wrapper">
                    <img src="{{ $item['qrUrl'] }}" alt="QR {{ $item['inventaris']->kode_inventaris }}">
                </div>
                <div class="item-code">{{ $item['inventaris']->kode_inventaris }}</div>
                <div class="item-name">{{ $item['inventaris']->nama_barang }}</div>
                <div class="item-meta">{{ $item['inventaris']->kategori ?: 'Inventaris' }} | {{ $item['inventaris']->lokasi ?: 'Kantor' }}</div>
            </div>
        @endforeach
    </div>
</body>
</html>
