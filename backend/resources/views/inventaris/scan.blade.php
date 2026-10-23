<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $inventaris ? $inventaris->nama_barang . ' (' . $inventaris->kode_inventaris . ')' : 'Aset Tidak Ditemukan' }} — PT Banyan Koalindo Lestari</title>
    <meta name="description" content="Informasi Detail Inventaris Barang PT Banyan Koalindo Lestari">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --brand-orange: #F05D24;
            --brand-orange-light: #FFF1EC;
            --brand-dark: #1A1D24;
            --brand-dark-soft: #222630;
            --gray-50: #F8FAFC;
            --gray-100: #F1F5F9;
            --gray-200: #E2E8F0;
            --gray-300: #CBD5E1;
            --gray-400: #94A3B8;
            --gray-500: #64748B;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1E293B;
            --gray-900: #0F172A;
            --status-ada: #10B981;
            --status-ada-bg: #ECFDF5;
            --status-rusak: #F59E0B;
            --status-rusak-bg: #FEF3C7;
            --status-hilang: #EF4444;
            --status-hilang-bg: #FEE2E2;
            --status-td: #6B7280;
            --status-td-bg: #F3F4F6;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-800);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            padding-bottom: 40px;
        }

        /* Top Brand Header */
        .public-navbar {
            background: linear-gradient(135deg, var(--brand-dark) 0%, var(--brand-dark-soft) 100%);
            color: #ffffff;
            padding: 16px 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-bottom: 3px solid var(--brand-orange);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-content {
            max-width: 680px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .navbar-logo {
            width: 38px;
            height: 38px;
            background: var(--brand-orange);
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(240, 93, 36, 0.4);
        }

        .navbar-text {
            display: flex;
            flex-direction: column;
        }

        .navbar-title {
            font-size: 14px;
            font-weight: 800;
            letter-spacing: -0.2px;
            color: #ffffff;
        }

        .navbar-company {
            font-size: 11px;
            color: var(--gray-400);
            font-weight: 500;
        }

        /* Main Container */
        .container {
            max-width: 680px;
            margin: 20px auto 0;
            padding: 0 16px;
        }

        /* Card Styles */
        .card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--gray-200);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            margin-bottom: 16px;
            overflow: hidden;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #FAFCFF;
        }

        .card-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title i {
            color: var(--brand-orange);
        }

        .card-body {
            padding: 20px;
        }

        /* Hero / Asset Title Section */
        .asset-hero {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--gray-200);
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .asset-code-badge {
            display: inline-block;
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 13px;
            font-weight: 700;
            color: var(--brand-orange);
            background: var(--brand-orange-light);
            padding: 4px 10px;
            border-radius: 6px;
            margin-bottom: 8px;
            border: 1px solid rgba(240, 93, 36, 0.2);
        }

        .asset-name {
            font-size: 20px;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1.3;
            margin-bottom: 12px;
        }

        .asset-meta-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-ada { background: var(--status-ada-bg); color: #065F46; border: 1px solid #A7F3D0; }
        .badge-rusak { background: var(--status-rusak-bg); color: #92400E; border: 1px solid #FDE68A; }
        .badge-hilang { background: var(--status-hilang-bg); color: #991B1B; border: 1px solid #FECACA; }
        .badge-td { background: var(--status-td-bg); color: #374151; border: 1px solid #E5E7EB; }

        .badge-kontrak-aktif { background: #EFF6FF; color: #1E40AF; border: 1px solid #BFDBFE; }
        .badge-kontrak-akan-habis { background: #FFFBEB; color: #B45309; border: 1px solid #FDE68A; }
        .badge-kontrak-habis { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
        .badge-no-kontrak { background: var(--gray-100); color: var(--gray-600); border: 1px solid var(--gray-200); }

        /* Asset Photo */
        .photo-wrapper {
            width: 100%;
            height: 240px;
            background: #F8FAFC;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--gray-200);
            margin-bottom: 18px;
        }

        .photo-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .photo-placeholder {
            text-align: center;
            color: var(--gray-400);
        }

        .photo-placeholder i {
            font-size: 42px;
            margin-bottom: 8px;
            display: block;
        }

        /* Info Item List (Mobile Friendly) */
        .info-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--gray-100);
            gap: 16px;
        }

        .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-key {
            font-size: 12px;
            color: var(--gray-500);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .info-key i {
            width: 14px;
            color: var(--gray-400);
        }

        .info-val {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--gray-900);
            text-align: right;
            word-break: break-word;
        }

        .info-val.mono {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            color: var(--brand-orange);
        }

        /* Long Text Box (Spesifikasi / Catatan) */
        .text-block {
            background: var(--gray-50);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            color: var(--gray-700);
            white-space: pre-line;
            line-height: 1.6;
            border: 1px solid var(--gray-200);
            margin-top: 6px;
        }

        /* Not Found State */
        .not-found-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 48px 24px;
            text-align: center;
            border: 1px solid var(--gray-200);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            margin-top: 30px;
        }

        .not-found-icon {
            width: 72px;
            height: 72px;
            background: #FEF2F2;
            color: #EF4444;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 16px;
            border: 2px solid #FEE2E2;
        }

        .not-found-title {
            font-size: 18px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 8px;
        }

        .not-found-desc {
            font-size: 14px;
            color: var(--gray-600);
            max-width: 380px;
            margin: 0 auto 20px;
            line-height: 1.5;
        }

        .not-found-code {
            display: inline-block;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            background: var(--gray-100);
            padding: 4px 10px;
            border-radius: 6px;
            color: var(--gray-800);
        }

        /* Footer */
        .public-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 11.5px;
            color: var(--gray-400);
            line-height: 1.6;
        }
    </style>
</head>
<body>

    {{-- Top Public Navbar --}}
    <header class="public-navbar">
        <div class="navbar-content">
            <div class="navbar-logo">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <div class="navbar-text">
                <span class="navbar-title">Sistem Inventaris Barang</span>
                <span class="navbar-company">PT Banyan Koalindo Lestari</span>
            </div>
        </div>
    </header>

    <main class="container">
        @if(!$inventaris)
            {{-- Aset Tidak Ditemukan --}}
            <div class="not-found-card">
                <div class="not-found-icon">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <h1 class="not-found-title">Aset Tidak Ditemukan</h1>
                <p class="not-found-desc">
                    Kode inventaris <span class="not-found-code">{{ $kode }}</span> tidak terdaftar dalam sistem inventaris kami.
                </p>
                <div style="font-size: 12px; color: var(--gray-400);">
                    Pastikan Barcode yang dipindai masih valid atau hubungi bagian inventaris kantor.
                </div>
            </div>
        @else
            {{-- Asset Hero Card --}}
            <div class="asset-hero">
                <span class="asset-code-badge">
                    <i class="fas fa-barcode"></i> {{ $inventaris->kode_inventaris }}
                </span>
                <h1 class="asset-name">{{ $inventaris->nama_barang }}</h1>
                <div class="asset-meta-tags">
                    @php
                        $statusClass = match($inventaris->status_aset) {
                            'Ada' => 'badge-ada',
                            'Rusak' => 'badge-rusak',
                            'Hilang' => 'badge-hilang',
                            default => 'badge-td'
                        };
                        $statusIcon = match($inventaris->status_aset) {
                            'Ada' => 'fa-circle-check',
                            'Rusak' => 'fa-triangle-exclamation',
                            'Hilang' => 'fa-circle-xmark',
                            default => 'fa-circle-question'
                        };
                    @endphp
                    <span class="badge {{ $statusClass }}">
                        <i class="fas {{ $statusIcon }}"></i> Status: {{ $inventaris->status_aset }}
                    </span>

                    @if($inventaris->kategori)
                    <span class="badge" style="background: var(--gray-100); color: var(--gray-700); border: 1px solid var(--gray-200);">
                        <i class="fas fa-tag"></i> {{ $inventaris->kategori }}
                    </span>
                    @endif

                    @if($inventaris->lokasi)
                    <span class="badge" style="background: var(--gray-100); color: var(--gray-700); border: 1px solid var(--gray-200);">
                        <i class="fas fa-location-dot"></i> {{ $inventaris->lokasi }}
                    </span>
                    @endif
                </div>
            </div>

            {{-- Asset Detail Info Card --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-circle-info"></i>
                        Informasi Spesifikasi Aset
                    </div>
                </div>
                <div class="card-body">
                    {{-- Foto Aset --}}
                    <div class="photo-wrapper">
                        @if($inventaris->foto)
                            <img src="{{ asset('storage/' . $inventaris->foto) }}" alt="Foto {{ $inventaris->nama_barang }}">
                        @else
                            <div class="photo-placeholder">
                                <i class="fas fa-image"></i>
                                <span>Foto aset belum tersedia</span>
                            </div>
                        @endif
                    </div>

                    {{-- Informasi Detail --}}
                    <div class="info-list">
                        <div class="info-row">
                            <span class="info-key"><i class="fas fa-barcode"></i> Kode Inventaris</span>
                            <span class="info-val mono">{{ $inventaris->kode_inventaris }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key"><i class="fas fa-box"></i> Nama Barang</span>
                            <span class="info-val">{{ $inventaris->nama_barang }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key"><i class="fas fa-tags"></i> Kategori</span>
                            <span class="info-val">{{ $inventaris->kategori ?: '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key"><i class="fas fa-copyright"></i> Merek / Brand</span>
                            <span class="info-val">{{ $inventaris->merek ?: '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key"><i class="fas fa-truck-field"></i> Vendor / Supplier</span>
                            <span class="info-val">{{ $inventaris->vendor ?: '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key"><i class="fas fa-location-dot"></i> Lokasi Penempatan</span>
                            <span class="info-val">{{ $inventaris->lokasi ?: '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key"><i class="fas fa-heart-pulse"></i> Kondisi Fisik</span>
                            <span class="info-val">{{ $inventaris->kondisi ?: '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key"><i class="fas fa-shield"></i> Status Keberadaan</span>
                            <span class="info-val">{{ $inventaris->status_aset }}</span>
                        </div>
                    </div>

                    @if($inventaris->spesifikasi)
                    <div style="margin-top: 18px;">
                        <span class="info-key" style="margin-bottom: 4px;"><i class="fas fa-microchip"></i> Spesifikasi Detail:</span>
                        <div class="text-block">{{ $inventaris->spesifikasi }}</div>
                    </div>
                    @endif

                    @if($inventaris->keterangan)
                    <div style="margin-top: 14px;">
                        <span class="info-key" style="margin-bottom: 4px;"><i class="fas fa-note-sticky"></i> Catatan Tambahan:</span>
                        <div class="text-block">{{ $inventaris->keterangan }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Informasi Kontrak Sewa Beli --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-file-contract"></i>
                        Status Kontrak Sewa Beli
                    </div>
                    <div>
                        @if($inventaris->kontrak)
                            @php
                                $skClass = match($inventaris->status_kontrak) {
                                    'AKTIF', 'Sedang Berjalan' => 'badge-kontrak-aktif',
                                    'AKAN BERAKHIR', 'Akan Berakhir' => 'badge-kontrak-akan-habis',
                                    default => 'badge-kontrak-habis'
                                };
                            @endphp
                            <span class="badge {{ $skClass }}">
                                {{ $inventaris->status_kontrak }}
                            </span>
                        @else
                            <span class="badge badge-no-kontrak">
                                Tidak Berkontrak
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($inventaris->kontrak)
                        <div class="info-list">
                            <div class="info-row">
                                <span class="info-key"><i class="fas fa-hashtag"></i> Nomor Kontrak</span>
                                <span class="info-val mono">{{ $inventaris->kontrak->kode_kontrak }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-key"><i class="fas fa-building"></i> Vendor Kontrak</span>
                                <span class="info-val">{{ $inventaris->kontrak->vendor ?: '-' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-key"><i class="fas fa-calendar-day"></i> Periode Kontrak</span>
                                <span class="info-val">
                                    {{ $inventaris->kontrak->tanggal_mulai ? $inventaris->kontrak->tanggal_mulai->isoFormat('D MMM Y') : '-' }} s/d 
                                    {{ $inventaris->kontrak->tanggal_berakhir ? $inventaris->kontrak->tanggal_berakhir->isoFormat('D MMM Y') : '-' }}
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-key"><i class="fas fa-hourglass-half"></i> Sisa Masa Kontrak</span>
                                <span class="info-val" style="color: var(--brand-orange);">
                                    {{ $inventaris->kontrak->sisa_kontrak ?: '-' }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div style="text-align: center; padding: 12px 0; color: var(--gray-500); font-size: 13px;">
                            <i class="fas fa-shield-halved" style="font-size: 24px; color: var(--gray-300); margin-bottom: 6px; display: block;"></i>
                            Aset ini terdaftar sebagai inventaris mandiri dan tidak terikat kontrak sewa beli.
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Footer --}}
        <footer class="public-footer">
            <div>Sistem Informasi Monitoring Inventaris & Kontrak Sewa Beli</div>
            <div>&copy; {{ date('Y') }} PT Banyan Koalindo Lestari. All rights reserved.</div>
        </footer>
    </main>

</body>
</html>
