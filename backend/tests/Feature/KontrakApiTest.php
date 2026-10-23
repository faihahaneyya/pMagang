<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Kontrak;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class KontrakApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function getTestUser(): User
    {
        return new User(['id' => 1, 'name' => 'Tester', 'email' => 'tester@example.com']);
    }

    /**
     * Uji 1: GET /api/kontrak tanpa autentikasi DITOLAK (401 Unauthorized).
     * Mencegah data keuangan kontrak (biaya_sewa) bocor ke publik.
     */
    public function test_get_kontrak_list_unauthenticated_is_rejected(): void
    {
        $response = $this->getJson('/api/kontrak');

        $response->assertStatus(401);
    }

    /**
     * Uji 2: GET /api/kontrak/{id} tanpa autentikasi DITOLAK (401 Unauthorized).
     */
    public function test_get_kontrak_detail_unauthenticated_is_rejected(): void
    {
        $response = $this->getJson('/api/kontrak/KON-2026-001');

        $response->assertStatus(401);
    }

    /**
     * Uji 3: GET /api/kontrak DENGAN autentikasi berhasil mengembalikan daftar kontrak JSON.
     */
    public function test_get_kontrak_list_authenticated_returns_json(): void
    {
        $response = $this->actingAs($this->getTestUser())->getJson('/api/kontrak');

        $response->assertStatus(200);
        $response->assertJsonStructure([
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
                ]
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]
        ]);
        $response->assertJson([
            'success' => true,
        ]);
    }

    /**
     * Uji 4: GET /api/kontrak dengan parameter search (terautentikasi).
     */
    public function test_get_kontrak_with_search_authenticated(): void
    {
        $response = $this->actingAs($this->getTestUser())->getJson('/api/kontrak?search=KON-2026-001');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'kode_kontrak' => 'KON-2026-001',
        ]);
    }

    /**
     * Uji 5: GET /api/kontrak/{id} untuk kontrak yang ada (terautentikasi).
     */
    public function test_get_kontrak_detail_authenticated_success(): void
    {
        $response = $this->actingAs($this->getTestUser())->getJson('/api/kontrak/KON-2026-001');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'kode_kontrak',
                'vendor',
                'status_kontrak',
                'inventaris',
            ]
        ]);
        $response->assertJson([
            'success' => true,
            'data' => [
                'kode_kontrak' => 'KON-2026-001',
            ]
        ]);
    }

    /**
     * Uji 6: GET /api/kontrak/{id} untuk ID tidak ditemukan (terautentikasi).
     */
    public function test_get_kontrak_detail_authenticated_not_found(): void
    {
        $response = $this->actingAs($this->getTestUser())->getJson('/api/kontrak/KON-TIDAK-ADA-999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Data kontrak tidak ditemukan.',
        ]);
    }

    /**
     * Uji 7: POST /api/kontrak tanpa autentikasi DITOLAK (401 Unauthorized).
     */
    public function test_create_kontrak_unauthenticated_is_rejected(): void
    {
        $response = $this->postJson('/api/kontrak', [
            'kode_kontrak' => 'KON-UNAUTH-001',
            'vendor' => 'Vendor Liar',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_berakhir' => '2026-12-31',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Uji 8: PUT /api/kontrak/{id} tanpa autentikasi DITOLAK (401 Unauthorized).
     */
    public function test_update_kontrak_unauthenticated_is_rejected(): void
    {
        $response = $this->putJson('/api/kontrak/KON-2026-001', [
            'vendor' => 'Vendor Baru Tanpa Izin',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Uji 9: DELETE /api/kontrak/{id} tanpa autentikasi DITOLAK (401 Unauthorized).
     */
    public function test_delete_kontrak_unauthenticated_is_rejected(): void
    {
        $response = $this->deleteJson('/api/kontrak/KON-2026-001');

        $response->assertStatus(401);
    }

    /**
     * Uji 10: Validasi gagal saat data mutasi tidak lengkap (dengan user terautentikasi).
     */
    public function test_create_kontrak_validation_fails_on_empty_data(): void
    {
        $response = $this->actingAs($this->getTestUser())->postJson('/api/kontrak', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['kode_kontrak', 'vendor', 'tanggal_mulai', 'tanggal_berakhir']);
    }

    /**
     * Uji 11: Validasi tanggal_berakhir harus >= tanggal_mulai.
     */
    public function test_create_kontrak_validation_fails_on_invalid_date_range(): void
    {
        $response = $this->actingAs($this->getTestUser())->postJson('/api/kontrak', [
            'kode_kontrak' => 'KON-TEST-INVALID-DATE',
            'vendor' => 'PT Vendor Uji',
            'tanggal_mulai' => '2026-12-31',
            'tanggal_berakhir' => '2026-01-01',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tanggal_berakhir']);
    }

    /**
     * Uji 12: Mutasi berhasil dengan user terautentikasi (otomatis di-rollback oleh DatabaseTransactions).
     */
    public function test_create_and_delete_kontrak_authenticated(): void
    {
        $user = $this->getTestUser();

        // Test Create
        $createResponse = $this->actingAs($user)->postJson('/api/kontrak', [
            'kode_kontrak' => 'KON-TEST-API-999',
            'vendor' => 'PT Rental Sukses API',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_berakhir' => '2027-01-01',
            'durasi' => '12 Bulan',
            'biaya_sewa' => 5000000,
            'keterangan' => 'Uji kontrak via API',
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJson([
            'success' => true,
            'data' => [
                'kode_kontrak' => 'KON-TEST-API-999',
                'status_kontrak' => 'AKTIF',
            ]
        ]);

        // Test Update
        $updateResponse = $this->actingAs($user)->putJson('/api/kontrak/KON-TEST-API-999', [
            'vendor' => 'PT Rental Sukses API Diperbarui',
            'biaya_sewa' => 6000000,
        ]);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJson([
            'success' => true,
            'data' => [
                'vendor' => 'PT Rental Sukses API Diperbarui',
                'biaya_sewa' => 6000000,
            ]
        ]);

        // Test Delete (Safe relation handling)
        $deleteResponse = $this->actingAs($user)->deleteJson('/api/kontrak/KON-TEST-API-999');
        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJson([
            'success' => true,
            'message' => 'Kontrak berhasil dihapus.',
        ]);
    }
}
