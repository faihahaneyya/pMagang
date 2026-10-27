<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RootResponseTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Memastikan route root (/) menampilkan halaman visual welcome bawaan Laravel,
     * bukan dashboard monolitik lama atau aplikasi pengelolaan data kedua.
     */
    public function test_root_returns_laravel_welcome_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        // Pastikan memuat elemen halaman welcome resmi Laravel
        $response->assertSee('Laravel');

        // Pastikan TIDAK memuat fragmen dashboard Blade inventaris lama
        $response->assertDontSee('dashboard-hero', false);
        $response->assertDontSee('sidebar-nav', false);
        $response->assertDontSee('Daftar Inventaris Terbaru', false);
        $response->assertDontSee('totalBiayaSewa', false);
    }

    /**
     * Memastikan route root (/) tetap mengembalikan JSON terstruktur jika diminta oleh API client.
     */
    public function test_root_returns_json_when_requested_by_api_client(): void
    {
        $response = $this->getJson('/');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJson([
            'status' => 'online',
            'api_status' => 'active',
        ]);
        $response->assertJsonFragment([
            'frontend_url' => config('app.frontend_url', 'http://localhost:5173'),
        ]);
    }

    /**
     * Memastikan rute web lama admin/inventaris mengarahkan pengguna resmi ke SPA frontend.
     */
    public function test_legacy_web_routes_redirect_to_frontend_spa(): void
    {
        $user = User::where('email', 'it@banyankoalindo.co.id')->first()
            ?: User::factory()->create([
                'name' => 'IT Administrator',
                'email' => 'it@banyankoalindo.co.id',
                'role' => 'IT',
            ]);

        $response = $this->actingAs($user)->get('/admin/inventaris');
        $response->assertRedirect('http://localhost:5173/inventaris');

        $responseKontrak = $this->actingAs($user)->get('/admin/kontrak');
        $responseKontrak->assertRedirect('http://localhost:5173/kontrak');

        $responsePengaturan = $this->actingAs($user)->get('/admin/pengaturan');
        $responsePengaturan->assertRedirect('http://localhost:5173');
    }
}
