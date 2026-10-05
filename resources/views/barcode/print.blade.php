<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label Barcode — {{ $inventaris->kode_inventaris }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #fff; color: #1F2937; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .label-card {
            width: 320px;
            border: 2px solid #F05D24;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            background: #fff;
            box-shadow: 0 4px 12px rgba(240, 93, 36, 0.15);
        }
        .company-header {
            font-size: 11px;
            font-weight: 700;
            color: #F05D24;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .system-title {
            font-size: 10px;
            color: #7E7E7E;
            margin-bottom: 12px;
        }
        .qr-wrapper {
            background: #fff;
            padding: 10px;
            border: 1px dashed #F8C3B0;
            border-radius: 8px;
            display: inline-block;
            margin-bottom: 12px;
        }
        .qr-wrapper img { width: 160px; height: 160px; display: block; }
        .item-code {
            font-family: 'Courier New', monospace;
            font-size: 16px;
            font-weight: 700;
            color: #1A1D24;
            letter-spacing: 1px;
        }
        .item-name {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-top: 4px;
        }
        .item-meta {
            font-size: 11px;
            color: #7E7E7E;
            margin-top: 2px;
        }
        @media print {
            body { padding: 0; background: none; }
            .label-card { box-shadow: none; border-color: #000; }
            .company-header { color: #000; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div style="text-align: center;">
        <div class="no-print" style="margin-bottom: 20px;">
            <button onclick="window.print()" style="background: #F05D24; color: #fff; border: none; padding: 10px 20px; font-weight: 600; border-radius: 6px; cursor: pointer;">
                🖨️ Cetak Label ini
            </button>
        </div>

        <div class="label-card">
            <div class="company-header">PT Banyan Koalindo Lestari</div>
            <div class="system-title">Inventaris Sewa Beli</div>
            <div class="qr-wrapper">
                <img src="{{ $qrUrl }}" alt="QR Code {{ $inventaris->kode_inventaris }}">
            </div>
            <div class="item-code">{{ $inventaris->kode_inventaris }}</div>
            <div class="item-name">{{ $inventaris->nama_barang }}</div>
            <div class="item-meta">{{ $inventaris->kategori ?: 'Inventaris' }} | {{ $inventaris->lokasi ?: 'Kantor' }}</div>
        </div>
    </div>
</body>
</html>
