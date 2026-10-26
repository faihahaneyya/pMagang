<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Label QR Code — {{ $inventaris->kode_inventaris }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #F8FAFC;
            color: #1F2937;
            padding: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            text-align: center;
        }
        .no-print {
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .btn-print {
            background: #F05D24;
            color: #ffffff;
            border: none;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(240, 93, 36, 0.3);
            transition: all 0.2s ease;
        }
        .btn-print:hover {
            background: #d84a15;
            transform: translateY(-1px);
        }
        .btn-close {
            background: #E2E8F0;
            color: #334155;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-close:hover {
            background: #CBD5E1;
        }
        .label-card {
            width: 320px;
            background: #ffffff;
            border: 2px solid #F05D24;
            border-radius: 12px;
            padding: 18px 20px;
            text-align: center;
            box-shadow: 0 4px 16px rgba(240, 93, 36, 0.12);
            margin: 0 auto;
        }
        .company-header {
            font-size: 12px;
            font-weight: 800;
            color: #F05D24;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 2px;
        }
        .system-title {
            font-size: 10px;
            font-weight: 600;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        .qrcode-box {
            background: #ffffff;
            padding: 12px;
            border: 1.5px dashed #F8C3B0;
            border-radius: 8px;
            margin-bottom: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .qrcode-svg-container {
            width: 150px;
            height: 150px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto;
        }
        .qrcode-svg-container svg {
            width: 100%;
            height: 100%;
            display: block;
        }
        .item-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 15px;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: 1.2px;
            margin-top: 8px;
        }
        .qrcode-label {
            font-size: 8.5px;
            font-weight: 700;
            color: #94A3B8;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 2px;
        }
        .item-name {
            font-size: 13.5px;
            font-weight: 700;
            color: #1E293B;
            line-height: 1.35;
            margin-bottom: 4px;
        }
        .item-meta {
            font-size: 11px;
            color: #64748B;
            font-weight: 500;
        }
        .item-ownership {
            margin-top: 6px;
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 4px;
            background: #FFF1EC;
            color: #F05D24;
            text-transform: uppercase;
        }
        .scan-url {
            font-family: 'Courier New', Courier, monospace;
            font-size: 8.5px;
            color: #64748B;
            word-break: break-all;
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px dashed #E2E8F0;
        }
        @media print {
            @page {
                size: auto;
                margin: 5mm;
            }
            body {
                background: none;
                padding: 0;
                min-height: auto;
                display: block;
            }
            .container {
                text-align: left;
            }
            .no-print {
                display: none !important;
            }
            .label-card {
                box-shadow: none;
                border: 1.5px solid #000;
                width: 300px;
                padding: 12px;
                margin: 0;
                page-break-inside: avoid;
            }
            .company-header {
                color: #000;
            }
            .system-title {
                color: #333;
            }
            .qrcode-box {
                border: 1px solid #999;
                padding: 10px;
            }
            .item-code {
                color: #000;
            }
            .qrcode-label {
                color: #555;
            }
            .item-ownership {
                background: #f1f5f9;
                color: #000;
                border: 1px solid #ccc;
            }
            .scan-url {
                color: #333;
                border-top: 1px solid #ccc;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print">
            <button class="btn-print" onclick="window.print()">
                🖨️ Cetak Label QR Code
            </button>
            <a href="{{ route('barcode.download', $inventaris->kode_inventaris) }}" class="btn-print" style="text-decoration: none; background: #334155; display: inline-flex; align-items: center; gap: 6px;" title="Unduh gambar QR Code (PNG)">
                ⬇️ Download QR Code
            </a>
            <button class="btn-close" onclick="window.close()">
                Tutup
            </button>
        </div>

        <div class="label-card">
            <div class="company-header">PT Banyan Koalindo Lestari</div>
            <div class="system-title">Inventaris Sewa Beli</div>
            
            <!-- QR Code (2D) Berisi URL Detail Aset -->
            <div class="qrcode-box">
                <div class="qrcode-svg-container">
                    {!! $qrCodeSvg ?? $barcodeSvg !!}
                </div>
                <div class="item-code">{{ $inventaris->kode_inventaris }}</div>
                <div class="qrcode-label">Format QR Code (2D)</div>
            </div>

            <div class="item-name">{{ $inventaris->nama_barang }}</div>
            <div class="item-meta">
                {{ $inventaris->kategori ?: 'Inventaris' }} &bull; {{ $inventaris->lokasi ?: 'Kantor' }}
            </div>
            <div class="item-ownership">
                {{ $inventaris->status_kepemilikan }}
            </div>
            <div class="scan-url">
                {{ $inventaris->barcode_url }}
            </div>
        </div>
    </div>
</body>
</html>
