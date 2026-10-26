<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Inventaris;
use App\Models\Kontrak;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DashboardApiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('email', 'it@banyankoalindo.co.id')->first()
            ?: User::factory()->create([
                'name' => 'IT Administrator',
                'email' => 'it@banyankoalindo.co.id',
                'role' => 'IT',
            ]);
    }

    public function test_dashboard_stats_unauthenticated_is_rejected(): void
    {
        $response = $this->getJson('/api/dashboard/stats');
        $response->assertStatus(401);
    }

    public function test_dashboard_stats_authenticated_returns_data(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/dashboard/stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_inventaris',
                'aset_ada',
                'aset_hilang',
                'aset_rusak',
                'aset_tidak_ditemukan',
                'kontrak_sedang_berjalan',
                'kontrak_akan_berakhir',
                'kontrak_habis',
                'total_kontrak',
                'total_biaya_sewa',
                'list_title',
                'filter',
                'recent_items',
            ],
        ]);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_dashboard_exports_authenticated(): void
    {
        $pdf = $this->actingAs($this->user)->get('/api/dashboard/export/pdf');
        $pdf->assertStatus(200);
        $pdf->assertHeader('Content-Disposition', 'attachment; filename=dashboard-inventaris.pdf');

        $excel = $this->actingAs($this->user)->get('/api/dashboard/export/excel');
        $excel->assertStatus(200);
        $excel->assertHeader('Content-Disposition', 'attachment; filename=dashboard-inventaris.xlsx');
    }

    public function test_inventaris_exports_authenticated(): void
    {
        $pdf = $this->actingAs($this->user)->get('/api/inventaris/export/pdf');
        $pdf->assertStatus(200);
        $this->assertStringContainsString('.pdf', (string) $pdf->headers->get('Content-Disposition'));

        $excel = $this->actingAs($this->user)->get('/api/inventaris/export/excel');
        $excel->assertStatus(200);
        $this->assertStringContainsString('.xlsx', (string) $excel->headers->get('Content-Disposition'));
    }

    public function test_kontrak_exports_authenticated(): void
    {
        $pdf = $this->actingAs($this->user)->get('/api/kontrak/export/pdf');
        $pdf->assertStatus(200);
        $this->assertStringContainsString('.pdf', (string) $pdf->headers->get('Content-Disposition'));

        $excel = $this->actingAs($this->user)->get('/api/kontrak/export/excel');
        $excel->assertStatus(200);
        $this->assertStringContainsString('.xlsx', (string) $excel->headers->get('Content-Disposition'));
    }

    public function test_barcode_download_api_authenticated(): void
    {
        $response = $this->actingAs($this->user)->get('/api/barcode/INV-IT-001/download');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_exports_and_barcode_api_unauthenticated_are_rejected(): void
    {
        $this->get('/api/dashboard/export/pdf')->assertStatus(401);
        $this->get('/api/dashboard/export/excel')->assertStatus(401);
        $this->get('/api/inventaris/export/pdf')->assertStatus(401);
        $this->get('/api/inventaris/export/excel')->assertStatus(401);
        $this->get('/api/kontrak/export/pdf')->assertStatus(401);
        $this->get('/api/kontrak/export/excel')->assertStatus(401);
        $this->get('/api/barcode/INV-IT-001/download')->assertStatus(401);
    }
}
