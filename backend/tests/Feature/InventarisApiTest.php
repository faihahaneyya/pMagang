<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Inventaris;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class InventarisApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function getTestUser(): User
    {
        return new User(['id' => 1, 'name' => 'Tester', 'email' => 'tester@example.com']);
    }

    /**
     * Uji 1: GET /api/inventaris tanpa autentikasi DITOLAK (401 Unauthorized).
     */
    public function test_get_inventaris_list_unauthenticated_is_rejected(): void
    {
        $response = $this->getJson('/api/inventaris');

        $response->assertStatus(401);
    }

    /**
     * Uji 2: GET /api/inventaris/{id} tanpa autentikasi DITOLAK (401 Unauthorized).
     */
    public function test_get_inventaris_detail_unauthenticated_is_rejected(): void
    {
        $response = $this->getJson('/api/inventaris/INV-IT-001');

        $response->assertStatus(401);
    }

    /**
     * Uji 3: GET /api/inventaris DENGAN autentikasi berhasil mengembalikan daftar inventaris JSON.
     */
    public function test_get_inventaris_list_authenticated_returns_json(): void
    {
        $response = $this->actingAs($this->getTestUser())->getJson('/api/inventaris');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'kode_inventaris',
                    'nama_barang',
                    'status_aset',
                    'status_kepemilikan',
                    'status_kontrak',
                    'photos',
                    'barcode_url',
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
     * Uji 4: GET /api/inventaris dengan parameter search (terautentikasi).
     */
    public function test_get_inventaris_with_search_authenticated(): void
    {
        $response = $this->actingAs($this->getTestUser())->getJson('/api/inventaris?search=INV-IT-001');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'kode_inventaris' => 'INV-IT-001',
        ]);
    }

    /**
     * Uji 5: GET /api/inventaris/{id} untuk item yang ada (terautentikasi).
     */
    public function test_get_inventaris_detail_authenticated_success(): void
    {
        $response = $this->actingAs($this->getTestUser())->getJson('/api/inventaris/INV-IT-001');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'kode_inventaris' => 'INV-IT-001',
            ]
        ]);
    }

    /**
     * Uji 6: GET /api/inventaris/{id} untuk ID tidak ditemukan (terautentikasi).
     */
    public function test_get_inventaris_detail_authenticated_not_found(): void
    {
        $response = $this->actingAs($this->getTestUser())->getJson('/api/inventaris/INV-TIDAK-ADA-999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Data inventaris tidak ditemukan.',
        ]);
    }

    /**
     * Uji 7: POST /api/inventaris tanpa autentikasi DITOLAK (401 Unauthorized).
     */
    public function test_create_inventaris_unauthenticated_is_rejected(): void
    {
        $response = $this->postJson('/api/inventaris', [
            'kode_inventaris' => 'INV-UNAUTH-001',
            'nama_barang' => 'Barang Liar',
            'status_aset' => 'Ada',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Uji 8: PUT /api/inventaris/{id} tanpa autentikasi DITOLAK (401 Unauthorized).
     */
    public function test_update_inventaris_unauthenticated_is_rejected(): void
    {
        $response = $this->putJson('/api/inventaris/INV-IT-001', [
            'nama_barang' => 'Nama Baru Tanpa Izin',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Uji 9: DELETE /api/inventaris/{id} tanpa autentikasi DITOLAK (401 Unauthorized).
     */
    public function test_delete_inventaris_unauthenticated_is_rejected(): void
    {
        $response = $this->deleteJson('/api/inventaris/INV-IT-001');

        $response->assertStatus(401);
    }

    /**
     * Uji 10: Validasi gagal saat data mutasi tidak lengkap (dengan user terautentikasi).
     */
    public function test_create_inventaris_validation_fails_on_empty_data(): void
    {
        $response = $this->actingAs($this->getTestUser())->postJson('/api/inventaris', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['kode_inventaris', 'nama_barang', 'status_aset']);
    }

    /**
     * Uji 11: Mutasi berhasil dengan user terautentikasi (otomatis di-rollback oleh DatabaseTransactions).
     */
    public function test_create_and_delete_inventaris_authenticated(): void
    {
        $user = $this->getTestUser();

        // Test Create
        $createResponse = $this->actingAs($user)->postJson('/api/inventaris', [
            'kode_inventaris' => 'INV-TEST-API-999',
            'nama_barang' => 'Monitor Samsung 27 Inch API',
            'kategori' => 'Elektronik',
            'merek' => 'Samsung',
            'status_aset' => 'Ada',
            'lokasi' => 'Ruang Server',
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJson([
            'success' => true,
            'data' => [
                'kode_inventaris' => 'INV-TEST-API-999',
                'status_kepemilikan' => 'MILIK PERUSAHAAN',
            ]
        ]);

        // Test Update
        $updateResponse = $this->actingAs($user)->putJson('/api/inventaris/INV-TEST-API-999', [
            'nama_barang' => 'Monitor Samsung 27 Inch Diperbarui',
            'status_aset' => 'Ada',
        ]);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJson([
            'success' => true,
            'data' => [
                'nama_barang' => 'Monitor Samsung 27 Inch Diperbarui',
            ]
        ]);

        // Test Delete
        $deleteResponse = $this->actingAs($user)->deleteJson('/api/inventaris/INV-TEST-API-999');
        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJson([
            'success' => true,
            'message' => 'Inventaris berhasil dihapus.',
        ]);
    }
}
