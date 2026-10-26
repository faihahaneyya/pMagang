<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventaris extends Model
{
    protected $table = 'inventaris';

    protected $fillable = [
        'kode_inventaris',
        'nama_barang',
        'kategori',
        'merek',
        'tipe_model',
        'spesifikasi',
        'nomor_seri',
        'vendor',
        'lokasi',
        'pic',
        'kondisi',
        'status_aset',
        'keterangan',
        'foto',
        'kontrak_id',
    ];

    protected $casts = [
        'status_aset' => 'string',
    ];

    public function kontrak(): BelongsTo
    {
        return $this->belongsTo(Kontrak::class, 'kontrak_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(InventoryPhoto::class, 'inventaris_id');
    }

    /**
     * Hitung status kepemilikan otomatis berdasarkan aturan bisnis:
     * - Jika tidak ada kontrak sewa: MILIK PERUSAHAAN (aset mandiri).
     * - Jika memiliki kontrak sewa:
     *   - Tanggal berakhir sudah lewat (status kontrak BERAKHIR): MILIK PERUSAHAAN.
     *   - Selama masa sewa (status kontrak AKTIF atau AKAN BERAKHIR, termasuk hari ini): SEWA.
     */
    public function getStatusKepemilikanAttribute(): string
    {
        if (!$this->kontrak_id || !$this->kontrak) {
            return 'MILIK PERUSAHAAN';
        }

        if ($this->kontrak->status_kontrak === 'BERAKHIR') {
            return 'MILIK PERUSAHAAN';
        }

        return 'SEWA';
    }

    public function getStatusKontrakAttribute(): string
    {
        if (!$this->kontrak_id || !$this->kontrak) {
            return '-';
        }
        return $this->kontrak->status_kontrak;
    }

    public function getKodeKontrakAttribute(): string
    {
        if (!$this->kontrak_id || !$this->kontrak) {
            return '-';
        }
        return $this->kontrak->kode_kontrak;
    }

    public function getSisaKontrakAttribute(): string
    {
        if (!$this->kontrak_id || !$this->kontrak) {
            return '-';
        }
        return $this->kontrak->sisa_kontrak;
    }

    public function getBarcodeUrlAttribute(): string
    {
        // 1. Prioritaskan parameter eksplisit jika dikirim via query string (misal dari cetak frontend)
        if (request()->filled('frontend_url')) {
            $base = rtrim((string) request()->get('frontend_url'), '/');
            return "{$base}/aset/{$this->kode_inventaris}";
        }

        // 2. Jika dipicu dari frontend (lewat header Referer), gunakan origin frontend pemanggil
        if (request()->hasHeader('referer')) {
            $referer = (string) request()->header('referer');
            $parts = parse_url($referer);
            if (!empty($parts['scheme']) && !empty($parts['host'])) {
                $port = !empty($parts['port']) ? ':' . $parts['port'] : '';
                $base = "{$parts['scheme']}://{$parts['host']}{$port}";
                return "{$base}/aset/{$this->kode_inventaris}";
            }
        }

        // 3. Jika request langsung ke server backend dengan host selain localhost (akses LAN HP)
        if (request()->getHost() && !in_array(request()->getHost(), ['localhost', '127.0.0.1'])) {
            $scheme = request()->getScheme();
            $host = request()->getHost();
            $frontendPort = env('FRONTEND_PORT', '5173');
            $portStr = $frontendPort ? ":{$frontendPort}" : '';
            return "{$scheme}://{$host}{$portStr}/aset/{$this->kode_inventaris}";
        }

        // 4. Default konfigurasi app.frontend_url
        $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
        return "{$frontendUrl}/aset/{$this->kode_inventaris}";
    }

    /**
     * Generate QR Code berbentuk persegi dalam format SVG lokal (vektor murni, tajam, presisi).
     * Berisi URL lengkap menuju detail publik aset (/aset/{kode}).
     */
    public function getQrCodeSvgAttribute(): string
    {
        return (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size(160)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($this->barcode_url);
    }

    /**
     * Generate QR Code dalam format PNG binary murni (via BaconQrCode ByteMatrix + PHP GD lokal).
     * Tidak membutuhkan ekstensi imagick.
     */
    public function getQrCodePng(int $targetSize = 300): string
    {
        $qr = \BaconQrCode\Encoder\Encoder::encode($this->barcode_url, \BaconQrCode\Common\ErrorCorrectionLevel::M(), 'UTF-8');
        $matrix = $qr->getMatrix();
        $matrixWidth = $matrix->getWidth();
        $matrixHeight = $matrix->getHeight();
        // Standar ISO/IEC 18004 quiet zone (margin) adalah 4 modul agar mudah dipindai kamera HP
        $margin = 4;
        $moduleSize = (int) max(1, floor($targetSize / ($matrixWidth + ($margin * 2))));
        $totalWidth = ($matrixWidth + ($margin * 2)) * $moduleSize;
        $totalHeight = ($matrixHeight + ($margin * 2)) * $moduleSize;

        $im = imagecreatetruecolor($totalWidth, $totalHeight);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagefilledrectangle($im, 0, 0, $totalWidth, $totalHeight, $white);

        for ($y = 0; $y < $matrixHeight; $y++) {
            for ($x = 0; $x < $matrixWidth; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $x1 = ($x + $margin) * $moduleSize;
                    $y1 = ($y + $margin) * $moduleSize;
                    $x2 = $x1 + $moduleSize - 1;
                    $y2 = $y1 + $moduleSize - 1;
                    imagefilledrectangle($im, $x1, $y1, $x2, $y2, $black);
                }
            }
        }

        ob_start();
        imagepng($im);
        $pngData = ob_get_clean();
        imagedestroy($im);

        return (string) $pngData;
    }

    /**
     * QR Code dalam format Base64 Data URI PNG.
     */
    public function getQrCodeBase64Attribute(): string
    {
        return 'data:image/png;base64,' . base64_encode($this->getQrCodePng(260));
    }

    /**
     * Alias kompatibilitas untuk barcode_svg -> QR Code SVG
     */
    public function getBarcodeSvgAttribute(): string
    {
        return $this->qr_code_svg;
    }

    /**
     * Alias kompatibilitas untuk getBarcodePng() -> QR Code PNG
     */
    public function getBarcodePng(int $widthFactor = 2, int $height = 60): string
    {
        return $this->getQrCodePng(300);
    }

    /**
     * Alias kompatibilitas untuk barcode_base64 -> QR Code Base64
     */
    public function getBarcodeBase64Attribute(): string
    {
        return $this->qr_code_base64;
    }

    /**
     * Helper untuk mengambil seluruh foto (menggabungkan tabel baru & fallback kolom lama)
     */
    public function getAllPhotosAttribute(): array
    {
        $list = [];
        if ($this->relationLoaded('photos') || $this->photos()->exists()) {
            foreach ($this->photos as $p) {
                $list[] = [
                    'id' => $p->id,
                    'path' => $p->foto_path,
                    'url' => asset('storage/' . $p->foto_path),
                    'caption' => $p->keterangan_foto,
                ];
            }
        }
        if (empty($list) && !empty($this->foto)) {
            $list[] = [
                'id' => null,
                'path' => $this->foto,
                'url' => asset('storage/' . $this->foto),
                'caption' => 'Foto Utama',
            ];
        }
        return $list;
    }
}
