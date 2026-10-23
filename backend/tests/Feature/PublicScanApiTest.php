<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicScanApiTest extends TestCase
{
    /**
     * Uji 1: GET /api/scan/{kode} publik dapat diakses tanpa autentikasi (200 OK).
     */
    public function test_public_scan_accessible_without_auth(): void
    {
        $response = $this->getJson('/api/scan/INV-IT-001');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'kode_inventaris' => 'INV-IT-001',
                'status_kepemilikan' => 'SEWA',
                'status_kontrak' => 'AKTIF',
            ],
        ]);
    }

    /**
     * Uji 2: Respon scan publik HANYA mengandung field yang diizinkan (strict whitelist).
     */
    public function test_public_scan_contains_only_strictly_whitelisted_fields(): void
    {
        $response = $this->getJson('/api/scan/INV-IT-001');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'kode_inventaris',
                'nama_barang',
                'spesifikasi',
                'lokasi',
                'foto',
                'photos',
                'status_kepemilikan',
                'status_kontrak',
                'kontrak' => [
                    'tanggal_mulai',
                    'tanggal_berakhir',
                    'sisa_hari',
                    'status_kontrak',
                ],
            ],
        ]);
    }

    /**
     * Uji 3: Respon scan publik TIDAK PERNAH memuat biaya sewa atau informasi finansial perusahaan.
     */
    public function test_public_scan_never_exposes_financial_data(): void
    {
        $response = $this->getJson('/api/scan/INV-IT-001');

        $response->assertStatus(200);

        $jsonString = strtolower($response->getContent());
        $this->assertStringNotContainsString('biaya_sewa', $jsonString);
        $this->assertStringNotContainsString('total_biaya', $jsonString);
        $this->assertStringNotContainsString('nilai_kontrak', $jsonString);

        $data = $response->json('data');
        $this->assertArrayNotHasKey('biaya_sewa', $data);
        if (isset($data['kontrak'])) {
            $this->assertArrayNotHasKey('biaya_sewa', $data['kontrak']);
            $this->assertArrayNotHasKey('total_biaya', $data['kontrak']);
            $this->assertArrayNotHasKey('nilai_kontrak', $data['kontrak']);
        }
    }

    /**
     * Uji 4: Respon scan publik menampilkan lokasi namun TIDAK menampilkan PIC, nomor_seri, atau vendor.
     */
    public function test_public_scan_exposes_location_while_protecting_pic_and_confidential_fields(): void
    {
        $response = $this->getJson('/api/scan/INV-IT-001');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('lokasi', $data);
        $this->assertEquals('Ruang IT', $data['lokasi']);

        // Pastikan field PIC dan confidential tetap terlindungi
        $this->assertArrayNotHasKey('pic', $data, 'PIC harus tetap dilindungi untuk admin internal');
        $this->assertArrayNotHasKey('nomor_seri', $data);
        if (isset($data['kontrak'])) {
            $this->assertArrayNotHasKey('vendor', $data['kontrak']);
        }
    }

    /**
     * Uji 5: Foto pada respon scan publik tidak membocorkan internal storage path atau DB ID.
     */
    public function test_public_scan_photos_do_not_leak_internal_paths_or_ids(): void
    {
        $response = $this->getJson('/api/scan/INV-IT-001');

        $response->assertStatus(200);

        $photos = $response->json('data.photos');
        $this->assertIsArray($photos);

        foreach ($photos as $photo) {
            $this->assertArrayHasKey('url', $photo);
            $this->assertArrayHasKey('caption', $photo);
            $this->assertArrayNotHasKey('id', $photo);
            $this->assertArrayNotHasKey('path', $photo);
            $this->assertArrayNotHasKey('inventaris_id', $photo);
        }
    }

    /**
     * Uji 6: Alias /api/public/scan/{kode} berfungsi identik dan aman.
     */
    public function test_public_scan_alias_route(): void
    {
        $response = $this->getJson('/api/public/scan/INV-IT-001');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'kode_inventaris' => 'INV-IT-001',
            'lokasi' => 'Ruang IT',
        ]);
        $response->assertJsonMissing(['biaya_sewa', 'pic', 'vendor', 'nomor_seri']);
    }

    /**
     * Uji 7: Kode tidak ditemukan pada scan publik mengembalikan 404 JSON.
     */
    public function test_public_scan_not_found(): void
    {
        $response = $this->getJson('/api/scan/INV-KODE-PALSU-999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Aset inventaris tidak ditemukan.',
        ]);
    }
}
