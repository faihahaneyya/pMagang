<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Lembar Label QR Code</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #F8FAFC;
            color: #1F2937;
            padding: 24px;
        }
        .no-print {
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            padding: 16px 20px;
            border-radius: 12px;
            border: 1px solid #E2E8F0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .no-print h2 {
            font-size: 16px;
            font-weight: 800;
            color: #0F172A;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-group {
            display: flex;
            gap: 10px;
        }
        .btn-print {
            background: #F05D24;
            color: #fff;
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
        }
        .btn-close {
            background: #E2E8F0;
            color: #334155;
            border: none;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }
        .label-card {
            border: 2px solid #F05D24;
            border-radius: 10px;
            padding: 14px 14px;
            text-align: center;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(240, 93, 36, 0.08);
            page-break-inside: avoid;
            break-inside: avoid;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .company-header {
            font-size: 11px;
            font-weight: 800;
            color: #F05D24;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .system-title {
            font-size: 9px;
            font-weight: 600;
            color: #64748B;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .qrcode-box {
            background: #ffffff;
            padding: 10px;
            border: 1px dashed #F8C3B0;
            border-radius: 6px;
            margin-bottom: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .qrcode-svg-container {
            width: 130px;
            height: 130px;
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
            font-size: 14px;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: 1.2px;
            margin-top: 6px;
        }
        .qrcode-label {
            font-size: 7.5px;
            font-weight: 700;
            color: #94A3B8;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-top: 1px;
        }
        .item-name {
            font-size: 12.5px;
            font-weight: 700;
            color: #1E293B;
            line-height: 1.3;
            margin-top: 4px;
            margin-bottom: 4px;
        }
        .item-meta {
            font-size: 10.5px;
            color: #64748B;
            font-weight: 500;
        }
        .item-ownership {
            margin-top: 4px;
            display: inline-block;
            font-size: 9.5px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            background: #FFF1EC;
            color: #F05D24;
            text-transform: uppercase;
        }
        .scan-url {
            font-family: 'Courier New', Courier, monospace;
            font-size: 8px;
            color: #64748B;
            word-break: break-all;
            margin-top: 6px;
            padding-top: 4px;
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
            }
            .no-print {
                display: none !important;
            }
            .grid-container {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 8mm;
            }
            .label-card {
                border: 1.5px solid #000;
                box-shadow: none;
                padding: 10px;
                break-inside: avoid;
            }
            .company-header {
                color: #000;
            }
            .system-title {
                color: #333;
            }
            .qrcode-box {
                border: 1px solid #999;
                padding: 8px;
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
    <div class="no-print">
        <h2>🏷️ Lembar Cetak Label QR Code ({{ count($items) }} Item)</h2>
        <div class="btn-group">
            <button class="btn-print" onclick="window.print()">
                🖨️ Cetak Semua Label ({{ count($items) }})
            </button>
            <button class="btn-close" onclick="window.close()">
                Tutup
            </button>
        </div>
    </div>

    <div class="grid-container">
        @forelse($items as $item)
            <div class="label-card">
                <div>
                    <div class="company-header">PT Banyan Koalindo Lestari</div>
                    <div class="system-title">Inventaris Sewa Beli</div>
                    
                    <!-- QR Code (2D) -->
                    <div class="qrcode-box">
                        <div class="qrcode-svg-container">
                            {!! $item['qrCodeSvg'] ?? $item['barcodeSvg'] !!}
                        </div>
                        <div class="item-code">{{ $item['inventaris']->kode_inventaris }}</div>
                        <div class="qrcode-label">Format QR Code (2D)</div>
                    </div>

                    <div class="item-name">{{ $item['inventaris']->nama_barang }}</div>
                </div>
                <div>
                    <div class="item-meta">
                        {{ $item['inventaris']->kategori ?: 'Inventaris' }} &bull; {{ $item['inventaris']->lokasi ?: 'Kantor' }}
                    </div>
                    <div class="item-ownership">
                        {{ $item['inventaris']->status_kepemilikan }}
                    </div>
                    <div class="scan-url">
                        {{ $item['inventaris']->barcode_url }}
                    </div>
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #64748B;">
                Tidak ada QR Code yang dipilih untuk dicetak.
            </div>
        @endforelse
    </div>
</body>
</html>
