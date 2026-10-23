<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiHealthTest extends TestCase
{
    /**
     * Uji 1: Endpoint GET /api/health merespons status 200 dan mengembalikan JSON.
     */
    public function test_api_health_endpoint_returns_json(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJson([
            'status' => 'ok',
            'message' => 'Sistem Informasi Inventaris Sewa Beli API is healthy',
        ]);
        $response->assertJsonStructure([
            'status',
            'message',
            'timestamp',
        ]);
    }

    /**
     * Uji 2: Endpoint /api/health tidak mengekspos konfigurasi rahasia atau database.
     */
    public function test_api_health_does_not_leak_secrets(): void
    {
        $response = $this->getJson('/api/health');

        $content = $response->getContent();
        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('database', strtolower($content));
        $this->assertStringNotContainsString('mysql', strtolower($content));
        $this->assertStringNotContainsString('app_key', strtolower($content));
    }

    /**
     * Uji 3: CORS mengizinkan origin React frontend lokal (http://localhost:5173).
     */
    public function test_cors_allows_localhost_5173(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:5173',
        ])->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    /**
     * Uji 4: CORS tidak mengizinkan origin sembarangan secara wildcard.
     */
    public function test_cors_rejects_unauthorized_origin(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://unauthorized-domain.com',
        ])->getJson('/api/health');

        $response->assertStatus(200);
        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }
}
