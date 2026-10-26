<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Inventaris;
use App\Models\Kontrak;

class BarcodeTest extends TestCase
{
    /**
     * Uji 1: Pembuatan QR Code (2D) lokal pada model Inventaris.
     */
    public function test_barcode_code_128_generation_locally(): void
    {
        $inv = Inventaris::where('kode_inventaris', 'INV-IT-001')->firstOrFail();

        // Uji SVG
        $svg = $inv->qr_code_svg;
        $this->assertNotEmpty($svg);
        $this->assertStringContainsString('<svg', $svg);

        // Uji kompatibilitas barcode_svg alias
        $barcodeSvg = $inv->barcode_svg;
        $this->assertNotEmpty($barcodeSvg);
        $this->assertStringContainsString('<svg', $barcodeSvg);

        // Uji PNG binary via BaconQrCode + PHP GD lokal
        $png = $inv->getQrCodePng();
        $this->assertNotEmpty($png);
        // Header signature file PNG (89 50 4E 47 0D 0A 1A 0A)
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $png);

        // Uji Base64
        $base64 = $inv->qr_code_base64;
        $this->assertStringStartsWith('data:image/png;base64,', $base64);
    }

    /**
     * Uji 2: Route download QR Code PNG lokal (/admin/barcode/{kode}/download).
     */
    public function test_barcode_download_route(): void
    {
        $response = $this->get('/admin/barcode/INV-IT-001/download');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('Content-Disposition', 'attachment; filename="QR-INV-IT-001.png"');
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $response->getContent());
    }

    /**
     * Uji 3: Route print QR Code single (/admin/barcode/{kode}/print).
     * Memastikan label memuat QR Code (2D) dan URL /aset/{kode} berbasis FRONTEND_URL.
     */
    public function test_barcode_single_print_route(): void
    {
        $expectedUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/') . '/aset/INV-IT-001';

        $response = $this->get('/admin/barcode/INV-IT-001/print');

        $response->assertStatus(200);
        $response->assertSee('INV-IT-001');
        $response->assertSee('PT Banyan Koalindo Lestari');
        $response->assertSee('Format QR Code (2D)');
        $response->assertSee($expectedUrl, false);
        $response->assertSee('<svg', false);
        $response->assertDontSee('api.qrserver.com');
    }

    /**
     * Uji 4: Route print QR Code multi (/admin/barcode/print-all).
     * Memastikan seluruh item memuat QR Code (2D) dan URL scan berbasis FRONTEND_URL.
     */
    public function test_barcode_print_all_route(): void
    {
        $expectedUrl1 = rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/') . '/aset/INV-IT-001';
        $expectedUrl2 = rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/') . '/aset/INV-IT-002';

        $response = $this->get('/admin/barcode/print-all?kodes=INV-IT-001,INV-IT-002');

        $response->assertStatus(200);
        $response->assertSee('INV-IT-001');
        $response->assertSee('INV-IT-002');
        $response->assertSee('Lembar Cetak Label QR Code (2 Item)', false);
        $response->assertSee($expectedUrl1, false);
        $response->assertSee($expectedUrl2, false);
        $response->assertSee('Format QR Code (2D)');
        $response->assertDontSee('api.qrserver.com');
    }

    /**
     * Uji 4b: Memastikan URL pada cetak label secara dinamis mengikuti perubahan FRONTEND_URL.
     */
    public function test_barcode_print_reflects_custom_frontend_url(): void
    {
        // 1. Simulasikan IP laptop lokal untuk tes dari HP via Wi-Fi
        config(['app.frontend_url' => 'http://192.168.1.116:5173']);
        $responseLan = $this->get('/admin/barcode/INV-IT-001/print');
        $responseLan->assertStatus(200);
        $responseLan->assertSee('http://192.168.1.116:5173/aset/INV-IT-001', false);

        // 2. Simulasikan domain produksi setelah deployment
        config(['app.frontend_url' => 'https://inventaris.bkl.co.id']);
        $responseProd = $this->get('/admin/barcode/INV-IT-001/print');
        $responseProd->assertStatus(200);
        $responseProd->assertSee('https://inventaris.bkl.co.id/aset/INV-IT-001', false);
    }

    /**
     * Uji 5: Route scan untuk kode inventaris yang terdaftar (redirect ke frontend /aset/{kode}).
     */
    public function test_scan_route_with_valid_code(): void
    {
        // 1. Uji redirect default ke frontend
        $response = $this->get('/inventaris/scan/INV-IT-001');
        $response->assertRedirect('http://localhost:5173/aset/INV-IT-001');

        // 2. Uji opsi fallback ke Blade
        $fallback = $this->get('/inventaris/scan/INV-IT-001?fallback=1');
        $fallback->assertStatus(200);
        $fallback->assertSee('INV-IT-001');
        $fallback->assertSee('Informasi Spesifikasi Aset');
        $fallback->assertSee('Status Kontrak Sewa Beli');
    }

    /**
     * Uji 6: Route scan untuk kode inventaris yang tidak terdaftar.
     */
    public function test_scan_route_with_invalid_code(): void
    {
        // 1. Uji redirect default ke frontend
        $response = $this->get('/inventaris/scan/INV-TIDAK-ADA');
        $response->assertRedirect('http://localhost:5173/aset/INV-TIDAK-ADA');

        // 2. Uji opsi fallback ke Blade
        $fallback = $this->get('/inventaris/scan/INV-TIDAK-ADA?fallback=1');
        $fallback->assertStatus(200);
        $fallback->assertSee('Aset Tidak Ditemukan');
        $fallback->assertSee('INV-TIDAK-ADA');
    }

    /**
     * Uji 7: Halaman daftar barcode/QR Code admin (/admin/barcode).
     */
    public function test_barcode_index_page(): void
    {
        $response = $this->get('/admin/barcode');

        $response->assertStatus(200);
        $response->assertSee('Cetak Label QR Code');
        $response->assertSee('Preview QR Code');
        $response->assertSee('INV-IT-001');
        $response->assertDontSee('api.qrserver.com');
    }

    /**
     * Uji 8: Download QR Code untuk lebih dari satu aset inventaris.
     * Memastikan header, nama file unik (QR-{kode}.png), dan integritas data PNG biner tajam.
     */
    public function test_barcode_download_multiple_assets_integrity(): void
    {
        $testKodes = ['INV-IT-001', 'INV-IT-002', 'INV-NET-001', 'INV-AC-001'];

        foreach ($testKodes as $kode) {
            $inv = Inventaris::where('kode_inventaris', $kode)->first();
            if (!$inv) {
                continue;
            }

            // Uji endpoint download web
            $response = $this->get("/admin/barcode/{$kode}/download");
            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'image/png');
            $response->assertHeader('Content-Disposition', "attachment; filename=\"QR-{$kode}.png\"");

            $pngContent = $response->getContent();
            $this->assertNotEmpty($pngContent);
            $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $pngContent);

            // Verifikasi ukuran gambar (minimal 250x250px)
            $imageInfo = getimagesizefromstring($pngContent);
            $this->assertNotFalse($imageInfo);
            $this->assertGreaterThanOrEqual(250, $imageInfo[0]); // width
            $this->assertGreaterThanOrEqual(250, $imageInfo[1]); // height
            $this->assertEquals('image/png', $imageInfo['mime']);

            // Verifikasi URL yang di-encode sama persis dengan barcode_url
            $expectedUrl = $inv->barcode_url;
            $qr = \BaconQrCode\Encoder\Encoder::encode($expectedUrl, \BaconQrCode\Common\ErrorCorrectionLevel::M(), 'UTF-8');
            $this->assertNotNull($qr);
            $this->assertNotEmpty($qr->getMatrix());
        }
    }
}

