<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kontrak;
use App\Models\Inventaris;
use Carbon\Carbon;

class InventarisSeeder extends Seeder
{
    public function run(): void
    {
        // Kontrak data
        $kontrakData = [
            [
                'kode_kontrak' => 'KON-2026-001',
                'vendor' => 'PT Teknologi Maju',
                'tanggal_mulai' => '2026-01-01',
                'tanggal_berakhir' => '2026-12-31',
                'durasi' => '12 Bulan',
                'biaya_sewa' => 5000000,
                'keterangan' => 'Kontrak sewa beli perangkat IT untuk kebutuhan kantor pusat',
            ],
            [
                'kode_kontrak' => 'KON-2026-002',
                'vendor' => 'PT Solusi Digital',
                'tanggal_mulai' => '2026-01-15',
                'tanggal_berakhir' => Carbon::today()->addDays(15)->format('Y-m-d'),
                'durasi' => null,
                'biaya_sewa' => 3500000,
                'keterangan' => 'Kontrak sewa beli printer dan scanner',
            ],
            [
                'kode_kontrak' => 'KON-2025-001',
                'vendor' => 'PT Furnitur Nusantara',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_berakhir' => '2025-12-31',
                'durasi' => '12 Bulan',
                'biaya_sewa' => 2000000,
                'keterangan' => 'Kontrak sewa beli furnitur kantor',
            ],
            [
                'kode_kontrak' => 'KON-2026-003',
                'vendor' => 'PT Networking Pro',
                'tanggal_mulai' => '2026-03-01',
                'tanggal_berakhir' => '2027-02-28',
                'durasi' => '12 Bulan',
                'biaya_sewa' => 8000000,
                'keterangan' => 'Kontrak sewa beli perangkat jaringan',
            ],
            [
                'kode_kontrak' => 'KON-2026-004',
                'vendor' => 'PT AC Sejuk',
                'tanggal_mulai' => '2026-07-01',
                'tanggal_berakhir' => Carbon::today()->addDays(25)->format('Y-m-d'),
                'durasi' => null,
                'biaya_sewa' => 1500000,
                'keterangan' => 'Kontrak sewa beli AC dan pendingin ruangan',
            ],
        ];

        $kontrakIds = [];
        foreach ($kontrakData as $data) {
            $k = Kontrak::create($data);
            $kontrakIds[$data['kode_kontrak']] = $k->id;
        }

        // Inventaris data
        $inventarisData = [
            [
                'kode_inventaris' => 'INV-IT-001',
                'nama_barang' => 'Laptop Lenovo ThinkPad E15',
                'kategori' => 'Komputer & Laptop',
                'merek' => 'Lenovo',
                'spesifikasi' => 'Intel Core i7 11th Gen, RAM 16GB, SSD 512GB, Display 15.6 FHD',
                'vendor' => 'PT Teknologi Maju',
                'lokasi' => 'Ruang IT',
                'kondisi' => 'Baik',
                'status_aset' => 'Ada',
                'keterangan' => 'Laptop untuk staff IT, termasuk charger',
                'kontrak_id' => $kontrakIds['KON-2026-001'],
            ],
            [
                'kode_inventaris' => 'INV-IT-002',
                'nama_barang' => 'Monitor LG 24 inch',
                'kategori' => 'Komputer & Laptop',
                'merek' => 'LG',
                'spesifikasi' => 'IPS Panel, 75Hz, Full HD 1080p, HDMI & DisplayPort',
                'vendor' => 'PT Teknologi Maju',
                'lokasi' => 'Ruang IT',
                'kondisi' => 'Baik',
                'status_aset' => 'Ada',
                'keterangan' => 'Monitor IPS Full HD untuk workstation',
                'kontrak_id' => $kontrakIds['KON-2026-001'],
            ],
            [
                'kode_inventaris' => 'INV-IT-003',
                'nama_barang' => 'Laptop Dell Inspiron 15',
                'kategori' => 'Komputer & Laptop',
                'merek' => 'Dell',
                'spesifikasi' => 'Intel Core i5 10th Gen, RAM 8GB, SSD 256GB',
                'vendor' => 'PT Teknologi Maju',
                'lokasi' => 'Ruang Keuangan',
                'kondisi' => 'Baik',
                'status_aset' => 'Hilang',
                'keterangan' => 'Laptop tidak ditemukan saat inventarisasi terakhir',
                'kontrak_id' => $kontrakIds['KON-2026-001'],
            ],
            [
                'kode_inventaris' => 'INV-IT-004',
                'nama_barang' => 'Printer Canon PIXMA',
                'kategori' => 'Printer & Scanner',
                'vendor' => 'PT Solusi Digital',
                'lokasi' => 'Ruang Administrasi',
                'kondisi' => 'Rusak Ringan',
                'status_aset' => 'Rusak',
                'keterangan' => 'Printer mengalami paper jam yang sering terjadi',
                'kontrak_id' => $kontrakIds['KON-2026-002'],
            ],
            [
                'kode_inventaris' => 'INV-IT-005',
                'nama_barang' => 'Scanner Epson DS-310',
                'kategori' => 'Printer & Scanner',
                'vendor' => 'PT Solusi Digital',
                'lokasi' => 'Ruang Administrasi',
                'kondisi' => 'Baik',
                'status_aset' => 'Ada',
                'keterangan' => 'Scanner dokumen portabel',
                'kontrak_id' => $kontrakIds['KON-2026-002'],
            ],
            [
                'kode_inventaris' => 'INV-FUR-001',
                'nama_barang' => 'Meja Kerja Direktur',
                'kategori' => 'Furnitur',
                'vendor' => 'PT Furnitur Nusantara',
                'lokasi' => 'Ruang Direktur',
                'kondisi' => 'Baik',
                'status_aset' => 'Ada',
                'keterangan' => 'Meja kerja eksekutif kayu mahoni',
                'kontrak_id' => $kontrakIds['KON-2025-001'],
            ],
            [
                'kode_inventaris' => 'INV-FUR-002',
                'nama_barang' => 'Kursi Ergonomis Herman Miller',
                'kategori' => 'Furnitur',
                'vendor' => 'PT Furnitur Nusantara',
                'lokasi' => 'Ruang Direktur',
                'kondisi' => 'Baik',
                'status_aset' => 'Ada',
                'keterangan' => 'Kursi ergonomis premium untuk direktur',
                'kontrak_id' => $kontrakIds['KON-2025-001'],
            ],
            [
                'kode_inventaris' => 'INV-NET-001',
                'nama_barang' => 'Switch Cisco 24 Port',
                'kategori' => 'Jaringan',
                'vendor' => 'PT Networking Pro',
                'lokasi' => 'Server Room',
                'kondisi' => 'Baik',
                'status_aset' => 'Ada',
                'keterangan' => 'Switch manageable untuk koneksi LAN',
                'kontrak_id' => $kontrakIds['KON-2026-003'],
            ],
            [
                'kode_inventaris' => 'INV-NET-002',
                'nama_barang' => 'Router Mikrotik RB4011',
                'kategori' => 'Jaringan',
                'vendor' => 'PT Networking Pro',
                'lokasi' => 'Server Room',
                'kondisi' => 'Baik',
                'status_aset' => 'Ada',
                'keterangan' => 'Router utama gateway internet',
                'kontrak_id' => $kontrakIds['KON-2026-003'],
            ],
            [
                'kode_inventaris' => 'INV-NET-003',
                'nama_barang' => 'Access Point Ubiquiti UAP-AC',
                'kategori' => 'Jaringan',
                'vendor' => 'PT Networking Pro',
                'lokasi' => 'Ruang Meeting',
                'kondisi' => 'Baik',
                'status_aset' => 'Tidak Ditemukan',
                'keterangan' => 'AP belum bisa diverifikasi saat cek terakhir',
                'kontrak_id' => $kontrakIds['KON-2026-003'],
            ],
            [
                'kode_inventaris' => 'INV-AC-001',
                'nama_barang' => 'AC Split Daikin 2PK',
                'kategori' => 'Elektronik',
                'vendor' => 'PT AC Sejuk',
                'lokasi' => 'Ruang Direktur',
                'kondisi' => 'Baik',
                'status_aset' => 'Ada',
                'keterangan' => 'AC inverter hemat energi',
                'kontrak_id' => $kontrakIds['KON-2026-004'],
            ],
            [
                'kode_inventaris' => 'INV-AC-002',
                'nama_barang' => 'AC Split Daikin 1.5PK',
                'kategori' => 'Elektronik',
                'vendor' => 'PT AC Sejuk',
                'lokasi' => 'Ruang Meeting',
                'kondisi' => 'Baik',
                'status_aset' => 'Ada',
                'keterangan' => 'AC ruang meeting lantai 2',
                'kontrak_id' => $kontrakIds['KON-2026-004'],
            ],
            [
                'kode_inventaris' => 'INV-IT-006',
                'nama_barang' => 'Proyektor Epson EB-X41',
                'kategori' => 'Elektronik',
                'vendor' => 'PT Teknologi Maju',
                'lokasi' => 'Ruang Meeting',
                'kondisi' => 'Baik',
                'status_aset' => 'Hilang',
                'keterangan' => 'Proyektor hilang, sedang dalam investigasi',
                'kontrak_id' => null,
            ],
            [
                'kode_inventaris' => 'INV-IT-007',
                'nama_barang' => 'UPS APC 1000VA',
                'kategori' => 'Elektronik',
                'vendor' => 'PT Teknologi Maju',
                'lokasi' => 'Server Room',
                'kondisi' => 'Baik',
                'status_aset' => 'Hilang',
                'keterangan' => 'UPS untuk backup server',
                'kontrak_id' => null,
            ],
            [
                'kode_inventaris' => 'INV-IT-008',
                'nama_barang' => 'Laptop ASUS VivoBook 14',
                'kategori' => 'Komputer & Laptop',
                'vendor' => 'PT Teknologi Maju',
                'lokasi' => 'Ruang HRD',
                'kondisi' => 'Baik',
                'status_aset' => 'Ada',
                'keterangan' => 'Laptop untuk staff HRD',
                'kontrak_id' => $kontrakIds['KON-2026-001'],
            ],
        ];

        foreach ($inventarisData as $data) {
            Inventaris::create($data);
        }
    }
}
