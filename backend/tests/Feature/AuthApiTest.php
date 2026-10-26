<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\TokenMismatchException;

class AuthApiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Helper untuk membuat user uji sementara (diisolasi transaksi database).
     */
    protected function createTestUser(): User
    {
        $user = User::where('email', 'it@banyankoalindo.co.id')->first();
        if ($user) {
            $user->password = Hash::make('password123');
            $user->save();
            return $user;
        }

        return User::create([
            'name' => 'IT Administrator',
            'email' => 'it@banyankoalindo.co.id',
            'role' => 'IT',
            'password' => Hash::make('password123'),
        ]);
    }

    /**
     * Helper untuk membuat user finance uji sementara.
     */
    protected function createFinanceUser(): User
    {
        $user = User::where('email', 'finance@banyankoalindo.co.id')->first();
        if ($user) {
            $user->password = Hash::make('password123');
            $user->save();
            return $user;
        }

        return User::create([
            'name' => 'Finance Administrator',
            'email' => 'finance@banyankoalindo.co.id',
            'role' => 'Finance',
            'password' => Hash::make('password123'),
        ]);
    }

    /**
     * Uji 1: GET /api/csrf-cookie berhasil dan mengembalikan token CSRF beserta cookie sesi.
     */
    public function test_csrf_cookie_endpoint_returns_json_and_sets_cookies(): void
    {
        $response = $this->getJson('/api/csrf-cookie');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'CSRF cookie set successfully.',
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'csrf_token',
        ]);

        // Memastikan header Set-Cookie melampirkan XSRF-TOKEN
        $response->assertCookie('XSRF-TOKEN');
    }

    /**
     * Uji 2: POST /api/login gagal validasi jika email atau password kosong (422 Unprocessable Content).
     */
    public function test_login_validation_fails_on_empty_fields(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Uji 3: POST /api/login gagal jika kredensial tidak sesuai (401 Unauthorized).
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = $this->createTestUser();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'kata_sandi_salah',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Email atau kata sandi tidak valid.',
        ]);
        $this->assertGuest();
    }

    /**
     * Uji 4: POST /api/login berhasil dengan akun IT valid, memulai sesi pengguna.
     */
    public function test_login_success_with_valid_credentials(): void
    {
        $user = $this->createTestUser();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Login berhasil.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => 'it@banyankoalindo.co.id',
                'role' => 'IT',
            ],
        ]);

        // Memastikan password tidak bocor dalam respons JSON
        $this->assertArrayNotHasKey('password', $response->json('user'));

        // Memastikan pengguna terautentikasi di guard 'web'
        $this->assertAuthenticatedAs($user, 'web');
    }

    /**
     * Uji 4B: POST /api/login berhasil dengan akun Finance valid.
     */
    public function test_login_finance_account_success(): void
    {
        $finance = $this->createFinanceUser();

        $response = $this->postJson('/api/login', [
            'email' => $finance->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Login berhasil.',
            'user' => [
                'id' => $finance->id,
                'name' => $finance->name,
                'email' => 'finance@banyankoalindo.co.id',
                'role' => 'Finance',
            ],
        ]);

        $this->assertAuthenticatedAs($finance, 'web');
    }

    /**
     * Uji 4C: POST /api/login ditolak untuk akun di luar IT dan Finance (403 Forbidden).
     */
    public function test_login_with_unauthorized_account_is_rejected(): void
    {
        $unauthorized = User::create([
            'name' => 'Pengguna Lain',
            'email' => 'unauthorized@example.com',
            'role' => 'Guest',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $unauthorized->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Akun tidak diizinkan untuk mengakses sistem. Hanya akun IT dan Finance resmi PT Banyan Koalindo Lestari yang memiliki hak akses.',
        ]);
        $this->assertGuest();
    }

    /**
     * Uji 5: GET /api/me tanpa sesi ditolak (401 Unauthorized).
     */
    public function test_get_me_unauthenticated_is_rejected(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    /**
     * Uji 5B: GET /api/me tanpa sesi dengan header Accept text/html
     * tetap mengembalikan respons JSON 401 dan tidak melakukan redirect HTML.
     */
    public function test_api_unauthenticated_request_with_html_accept_still_returns_json_401(): void
    {
        $response = $this->get('/api/me', ['Accept' => 'text/html']);

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    /**
     * Uji 6: GET /api/me dengan sesi aktif berhasil mengembalikan data user beserta role.
     */
    public function test_get_me_authenticated_returns_user_profile(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user, 'web')->getJson('/api/me');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'IT',
            ],
        ]);
    }

    /**
     * Uji 7: POST /api/logout tanpa sesi ditolak (401 Unauthorized).
     */
    public function test_logout_unauthenticated_is_rejected(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    /**
     * Uji 8: POST /api/logout dengan sesi aktif berhasil mengakhiri sesi.
     */
    public function test_logout_authenticated_terminates_session(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user, 'web')->postJson('/api/logout');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);

        // Verifikasi pengguna telah logout
        $this->assertGuest('web');
    }

    /**
     * Uji 9: Akses CRUD inventaris dan kontrak berhasil menggunakan sesi pengguna aktif.
     */
    public function test_authenticated_session_can_access_inventory_and_contracts(): void
    {
        $user = $this->createTestUser();

        $invResponse = $this->actingAs($user, 'web')->getJson('/api/inventaris');
        $invResponse->assertStatus(200);
        $invResponse->assertJson(['success' => true]);

        $kontrakResponse = $this->actingAs($user, 'web')->getJson('/api/kontrak');
        $kontrakResponse->assertStatus(200);
        $kontrakResponse->assertJson(['success' => true]);
    }

    /**
     * Uji 10: Request POST mutasi tanpa token CSRF yang valid ditolak dengan HTTP 419 (CSRF token mismatch).
     */
    public function test_post_mutation_without_csrf_token_is_rejected_with_419(): void
    {
        // Enforce CSRF verification during test (menonaktifkan bypass unit test)
        $this->app->bind(ValidateCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends ValidateCsrfToken {
                protected function runningUnitTests(): bool
                {
                    return false;
                }
            };
        });

        // Request POST ke /api/login tanpa token CSRF melalui pipeline HTTP penuh
        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(419);
        $response->assertJson([
            'message' => 'CSRF token mismatch.',
        ]);
    }

    /**
     * Uji 11: Request POST mutasi dengan token CSRF yang valid diterima oleh middleware dan diproses.
     */
    public function test_post_mutation_with_valid_csrf_token_is_accepted(): void
    {
        $user = $this->createTestUser();

        // Enforce CSRF verification during test
        $this->app->bind(ValidateCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends ValidateCsrfToken {
                protected function runningUnitTests(): bool
                {
                    return false;
                }
            };
        });

        $sessionToken = 'test-valid-csrf-token-1234567890';

        // Kirim request POST ke /api/login dengan session token dan header X-CSRF-TOKEN yang cocok
        $response = $this->withSession(['_token' => $sessionToken])
            ->withHeaders(['X-CSRF-TOKEN' => $sessionToken])
            ->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'password123',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Login berhasil.',
        ]);
    }

    /**
     * Uji 13: Akun tanpa izin ditolak saat mengakses endpoint internal (403 Forbidden).
     */
    public function test_unauthorized_user_is_rejected_by_internal_middleware(): void
    {
        $unauthorized = User::create([
            'name' => 'Akun Tak Berizin',
            'email' => 'unauthorized@example.com',
            'role' => 'Guest',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($unauthorized, 'web')->getJson('/api/inventaris');
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Akses ditolak. Hanya akun IT dan Finance resmi PT Banyan Koalindo Lestari yang memiliki hak akses ke sistem internal.',
        ]);
    }

    /**
     * Uji 14: Akun Finance memiliki hak akses setara terhadap seluruh modul internal.
     */
    public function test_finance_user_has_equal_access_to_internal_endpoints(): void
    {
        $finance = $this->createFinanceUser();

        $invResponse = $this->actingAs($finance, 'web')->getJson('/api/inventaris');
        $invResponse->assertStatus(200);

        $kontrakResponse = $this->actingAs($finance, 'web')->getJson('/api/kontrak');
        $kontrakResponse->assertStatus(200);

        $dashResponse = $this->actingAs($finance, 'web')->getJson('/api/dashboard/stats');
        $dashResponse->assertStatus(200);
    }
}

