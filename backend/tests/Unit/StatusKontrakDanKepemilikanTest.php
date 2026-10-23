<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Kontrak;
use App\Models\Inventaris;
use App\Models\InventoryPhoto;
use Carbon\Carbon;

class StatusKontrakDanKepemilikanTest extends TestCase
{
    /**
     * Skenario 1: Sisa masa kontrak lebih dari 30 hari.
     * Status Kontrak = AKTIF
     * Status Kepemilikan = SEWA
     */
    public function test_sisa_lebih_dari_30_hari(): void
    {
        $today = Carbon::today();
        $kontrak = new Kontrak([
            'kode_kontrak' => 'CTR-TEST-001',
            'tanggal_mulai' => $today->copy()->subDays(30),
            'tanggal_berakhir' => $today->copy()->addDays(60),
        ]);

        $this->assertEquals('AKTIF', $kontrak->status_kontrak);
        $this->assertTrue($kontrak->sisa_hari > 30);

        $inventaris = new Inventaris(['kontrak_id' => 1]);
        $inventaris->setRelation('kontrak', $kontrak);

        $this->assertEquals('SEWA', $inventaris->status_kepemilikan);
        $this->assertEquals('AKTIF', $inventaris->status_kontrak);
    }

    /**
     * Skenario 2: Sisa masa kontrak tepat 30 hari.
     * Status Kontrak = AKAN BERAKHIR
     * Status Kepemilikan = SEWA
     */
    public function test_sisa_tepat_30_hari(): void
    {
        $today = Carbon::today();
        $kontrak = new Kontrak([
            'kode_kontrak' => 'CTR-TEST-002',
            'tanggal_mulai' => $today->copy()->subDays(60),
            'tanggal_berakhir' => $today->copy()->addDays(30),
        ]);

        $this->assertEquals('AKAN BERAKHIR', $kontrak->status_kontrak);
        $this->assertEquals(30, $kontrak->sisa_hari);

        $inventaris = new Inventaris(['kontrak_id' => 2]);
        $inventaris->setRelation('kontrak', $kontrak);

        $this->assertEquals('SEWA', $inventaris->status_kepemilikan);
        $this->assertEquals('AKAN BERAKHIR', $inventaris->status_kontrak);
    }

    /**
     * Skenario 3: Masa kontrak berakhir hari ini (0 hari tersisa / hari terakhir).
     * Status Kontrak = AKAN BERAKHIR
     * Status Kepemilikan = SEWA (tidak boleh berubah menjadi MILIK PERUSAHAAN sebelum/pada hari terakhir)
     */
    public function test_berakhir_hari_ini(): void
    {
        $today = Carbon::today();
        $kontrak = new Kontrak([
            'kode_kontrak' => 'CTR-TEST-003',
            'tanggal_mulai' => $today->copy()->subDays(90),
            'tanggal_berakhir' => $today->copy(),
        ]);

        $this->assertEquals('AKAN BERAKHIR', $kontrak->status_kontrak);
        $this->assertEquals(0, $kontrak->sisa_hari);

        $inventaris = new Inventaris(['kontrak_id' => 3]);
        $inventaris->setRelation('kontrak', $kontrak);

        $this->assertEquals('SEWA', $inventaris->status_kepemilikan);
        $this->assertEquals('AKAN BERAKHIR', $inventaris->status_kontrak);
    }

    /**
     * Skenario 4: Masa kontrak berakhir kemarin (tanggal berakhir sudah terlewati).
     * Status Kontrak = BERAKHIR
     * Status Kepemilikan = MILIK PERUSAHAAN (otomatis beralih setelah tanggal berakhir terlewati)
     */
    public function test_berakhir_kemarin_atau_terlewati(): void
    {
        $today = Carbon::today();
        $kontrak = new Kontrak([
            'kode_kontrak' => 'CTR-TEST-004',
            'tanggal_mulai' => $today->copy()->subDays(120),
            'tanggal_berakhir' => $today->copy()->subDay(),
        ]);

        $this->assertEquals('BERAKHIR', $kontrak->status_kontrak);
        $this->assertTrue($kontrak->sisa_hari < 0);

        $inventaris = new Inventaris(['kontrak_id' => 4]);
        $inventaris->setRelation('kontrak', $kontrak);

        $this->assertEquals('MILIK PERUSAHAAN', $inventaris->status_kepemilikan);
        $this->assertEquals('BERAKHIR', $inventaris->status_kontrak);
    }

    /**
     * Skenario 5: Inventaris tanpa kontrak sewa (aset beli / mandiri).
     * Status Kontrak = -
     * Status Kepemilikan = MILIK PERUSAHAAN
     */
    public function test_inventaris_tanpa_kontrak(): void
    {
        $inventaris = new Inventaris(['kontrak_id' => null]);

        $this->assertEquals('-', $inventaris->status_kontrak);
        $this->assertEquals('MILIK PERUSAHAAN', $inventaris->status_kepemilikan);
    }

    /**
     * Skenario 6: Relasi multiple foto dan fallback foto lama.
     */
    public function test_relasi_foto_dan_fallback(): void
    {
        $inventaris = new Inventaris([
            'kode_inventaris' => 'INV-TEST-001',
            'nama_barang' => 'Laptop ThinkPad',
            'foto' => 'photos/laptop_legacy.jpg',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $inventaris->photos());

        // Saat tabel relasi kosong tapi ada kolom foto lama:
        $photos = $inventaris->all_photos;
        $this->assertCount(1, $photos);
        $this->assertEquals('photos/laptop_legacy.jpg', $photos[0]['path']);
    }
}
