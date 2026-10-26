<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Kontrak;
use App\Models\Inventaris;

class Langkah13EndToEndTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Gunakan akun administrator resmi IT yang ada di database atau buat fallback
        $existingAdmin = User::whereIn('email', ['it@banyankoalindo.co.id', 'bklho@gmail.com'])->first();
        if ($existingAdmin) {
            $this->user = $existingAdmin;
        } else {
            $this->user = User::factory()->create([
                'name' => 'IT Administrator',
                'email' => 'it@banyankoalindo.co.id',
                'role' => 'IT',
            ]);
        }
    }

    /**
     * 1. Uji otorisasi API: unauthenticated request ditolak dengan 401.
     */
    public function test_1_unauthenticated_api_access_is_rejected(): void
    {
        $response = $this->getJson('/api/kontrak');
        $response->assertStatus(401);

        $detailResponse = $this->getJson('/api/kontrak/KON-2026-001');
        $detailResponse->assertStatus(401);
    }

    /**
     * 2. Uji daftar kontrak, pencarian, filter status, dan paginasi.
     */
    public function test_2_contract_list_search_filter_and_pagination(): void
    {
        // A. Daftar awal kontrak
        $listRes = $this->actingAs($this->user)->getJson('/api/kontrak');
        $listRes->assertStatus(200);
        $listRes->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'kode_kontrak',
                    'vendor',
                    'tanggal_mulai',
                    'tanggal_berakhir',
                    'biaya_sewa',
                    'total_biaya',
                    'sisa_hari',
                    'sisa_kontrak',
                    'status_kontrak',
                    'jumlah_inventaris',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
        $this->assertGreaterThanOrEqual(5, $listRes->json('meta.total'));

        // B. Pencarian berdasarkan vendor
        $searchRes = $this->actingAs($this->user)->getJson('/api/kontrak?search=Teknologi');
        $searchRes->assertStatus(200);
        $this->assertEquals(1, count($searchRes->json('data')));
        $this->assertEquals('KON-2026-001', $searchRes->json('data.0.kode_kontrak'));

        // C. Filter berdasarkan status kontrak
        $filterRes = $this->actingAs($this->user)->getJson('/api/kontrak?status_kontrak=AKTIF');
        $filterRes->assertStatus(200);
        foreach ($filterRes->json('data') as $item) {
            $this->assertContains($item['status_kontrak'], ['AKTIF', 'Sedang Berjalan']);
        }

        // D. Paginasi
        $pageRes = $this->actingAs($this->user)->getJson('/api/kontrak?per_page=2');
        $pageRes->assertStatus(200);
        $this->assertCount(2, $pageRes->json('data'));
        $this->assertEquals(2, $pageRes->json('meta.per_page'));
    }

    /**
     * 3. Uji detail kontrak dan relasi inventaris yang terikat.
     */
    public function test_3_contract_detail_and_inventory_relation(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/kontrak/KON-2026-001');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'kode_kontrak' => 'KON-2026-001',
                'vendor' => 'PT Teknologi Maju',
            ],
        ]);

        $this->assertArrayHasKey('inventaris', $response->json('data'));
        $inventarisList = $response->json('data.inventaris');
        $this->assertIsArray($inventarisList);
        $this->assertGreaterThan(0, count($inventarisList));
    }

    /**
     * 4. Uji validasi formulir dan respons error API (422).
     */
    public function test_4_form_validations_and_error_responses(): void
    {
        // Form Kosong
        $emptyRes = $this->actingAs($this->user)->postJson('/api/kontrak', []);
        $emptyRes->assertStatus(422);
        $emptyRes->assertJsonValidationErrors(['kode_kontrak', 'vendor', 'tanggal_mulai', 'tanggal_berakhir']);

        // Tanggal berakhir sebelum tanggal mulai
        $invalidDateRes = $this->actingAs($this->user)->postJson('/api/kontrak', [
            'kode_kontrak' => 'KON-TEST-INVALID-RANGE',
            'vendor' => 'PT Tanggal Salah',
            'tanggal_mulai' => '2026-10-10',
            'tanggal_berakhir' => '2026-01-01',
        ]);
        $invalidDateRes->assertStatus(422);
        $invalidDateRes->assertJsonValidationErrors(['tanggal_berakhir']);

        // Duplikasi nomor kontrak
        $dupRes = $this->actingAs($this->user)->postJson('/api/kontrak', [
            'kode_kontrak' => 'KON-2026-001',
            'vendor' => 'PT Vendor Duplikat',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_berakhir' => '2026-12-31',
        ]);
        $dupRes->assertStatus(422);
        $dupRes->assertJsonValidationErrors(['kode_kontrak']);
    }

    /**
     * 5. Uji CRUD lengkap kontrak uji: Create -> Verify in DB -> Update -> Verify in DB -> Delete -> Verify in DB.
     */
    public function test_5_crud_lifecycle_with_test_contract(): void
    {
        $testCode = 'KON-TEST-E2E-2026';

        // Pastikan bersih sebelum uji
        Kontrak::where('kode_kontrak', $testCode)->delete();

        $initialCount = Kontrak::count();
        $this->assertEquals(6, $initialCount, "Sebelum pengujian harus ada 6 kontrak asli");

        // 1. CREATE
        $createRes = $this->actingAs($this->user)->postJson('/api/kontrak', [
            'kode_kontrak' => $testCode,
            'vendor' => 'PT Pengujian Otomatis E2E BKL',
            'tanggal_mulai' => '2026-05-01',
            'tanggal_berakhir' => '2026-11-01',
            'durasi' => '6 Bulan',
            'biaya_sewa' => 12500000,
            'keterangan' => 'Kontrak dibuat untuk verifikasi CRUD E2E Langkah 13',
        ]);
        $createRes->assertStatus(201);
        $createRes->assertJson([
            'success' => true,
            'data' => [
                'kode_kontrak' => $testCode,
                'vendor' => 'PT Pengujian Otomatis E2E BKL',
            ],
        ]);

        $createdId = $createRes->json('data.id');
        $this->assertNotNull($createdId);

        // Verifikasi tersimpan di Database
        $dbContract = Kontrak::where('kode_kontrak', $testCode)->first();
        $this->assertNotNull($dbContract, "Kontrak uji harus tersimpan di database");
        $this->assertEquals('PT Pengujian Otomatis E2E BKL', $dbContract->vendor);

        // 2. READ via API
        $getRes = $this->actingAs($this->user)->getJson("/api/kontrak/{$createdId}");
        $getRes->assertStatus(200);
        $this->assertEquals($testCode, $getRes->json('data.kode_kontrak'));

        // 3. UPDATE
        $updateRes = $this->actingAs($this->user)->putJson("/api/kontrak/{$createdId}", [
            'kode_kontrak' => $testCode,
            'vendor' => 'PT Pengujian Otomatis E2E BKL - TERUBAH',
            'tanggal_mulai' => '2026-05-01',
            'tanggal_berakhir' => '2026-11-01',
            'durasi' => '6 Bulan',
            'biaya_sewa' => 17500000,
            'keterangan' => 'Keterangan diperbarui selama pengujian E2E',
        ]);
        $updateRes->assertStatus(200);
        $updateRes->assertJson([
            'success' => true,
            'data' => [
                'vendor' => 'PT Pengujian Otomatis E2E BKL - TERUBAH',
                'biaya_sewa' => 17500000,
            ],
        ]);

        // Verifikasi perubahan di Database
        $dbContractUpdated = Kontrak::where('kode_kontrak', $testCode)->first();
        $this->assertEquals('PT Pengujian Otomatis E2E BKL - TERUBAH', $dbContractUpdated->vendor);
        $this->assertEquals(17500000, (float) $dbContractUpdated->biaya_sewa);

        // 4. DELETE
        $deleteRes = $this->actingAs($this->user)->deleteJson("/api/kontrak/{$createdId}");
        $deleteRes->assertStatus(200);
        $deleteRes->assertJson([
            'success' => true,
            'message' => 'Kontrak berhasil dihapus.',
        ]);

        // Verifikasi benar-benar terhapus dari Database
        $dbContractDeleted = Kontrak::where('kode_kontrak', $testCode)->first();
        $this->assertNull($dbContractDeleted, "Kontrak uji harus terhapus dari database setelah pengujian");

        // Verifikasi seluruh kontrak asli tidak tersentuh
        $finalCount = Kontrak::count();
        $this->assertEquals($initialCount, $finalCount, "Setelah penghapusan, seluruh kontrak asli harus tetap ada di database");
    }

    /**
     * 6. Uji ekspor PDF dan Excel pada Kontrak, Dashboard, dan Inventaris beserta integritas file.
     */
    public function test_6_export_pdf_and_excel_file_integrity(): void
    {
        // A. Dashboard Export PDF
        $dashPdf = $this->actingAs($this->user)->get('/api/dashboard/export/pdf');
        $dashPdf->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $dashPdf->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $dashPdf->getContent());

        // B. Dashboard Export Excel
        $dashExcel = $this->actingAs($this->user)->get('/api/dashboard/export/excel');
        $dashExcel->assertStatus(200);
        $this->assertStringContainsString('.xlsx', (string) $dashExcel->headers->get('Content-Disposition'));
        $this->assertStringStartsWith("PK\x03\x04", $this->getResponseContent($dashExcel));

        // C. Inventaris Export PDF
        $invPdf = $this->actingAs($this->user)->get('/api/inventaris/export/pdf');
        $invPdf->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $invPdf->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $this->getResponseContent($invPdf));

        // D. Inventaris Export Excel
        $invExcel = $this->actingAs($this->user)->get('/api/inventaris/export/excel');
        $invExcel->assertStatus(200);
        $this->assertStringContainsString('.xlsx', (string) $invExcel->headers->get('Content-Disposition'));
        $this->assertStringStartsWith("PK\x03\x04", $this->getResponseContent($invExcel));

        // E. Kontrak Export PDF
        $kontrakPdf = $this->actingAs($this->user)->get('/api/kontrak/export/pdf');
        $kontrakPdf->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $kontrakPdf->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $this->getResponseContent($kontrakPdf));

        // F. Kontrak Export Excel
        $kontrakExcel = $this->actingAs($this->user)->get('/api/kontrak/export/excel');
        $kontrakExcel->assertStatus(200);
        $this->assertStringContainsString('.xlsx', (string) $kontrakExcel->headers->get('Content-Disposition'));
        $this->assertStringStartsWith("PK\x03\x04", $this->getResponseContent($kontrakExcel));
    }

    protected function getResponseContent($response): string
    {
        if ($response->baseResponse instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            return file_get_contents($response->baseResponse->getFile()->getPathname());
        }
        return $response->getContent();
    }

    /**
     * 7. Uji integritas akhir: relasi inventaris dan jumlah data database.
     */
    public function test_7_data_integrity_check(): void
    {
        $this->assertEquals(6, Kontrak::count(), "Database harus memiliki tepat 6 kontrak");
        $this->assertEquals(15, Inventaris::count(), "Database harus memiliki tepat 15 inventaris");

        // Pastikan relasi inventaris memiliki kontrak yang valid
        $inventarisWithKontrak = Inventaris::whereNotNull('kontrak_id')->get();
        $this->assertGreaterThan(0, $inventarisWithKontrak->count());
        foreach ($inventarisWithKontrak as $inv) {
            $this->assertNotNull($inv->kontrak, "Relasi inventaris ke kontrak harus valid");
            $this->assertNotEmpty($inv->kontrak->kode_kontrak);
        }
    }
}
