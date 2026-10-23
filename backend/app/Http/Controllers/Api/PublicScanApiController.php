<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventaris;
use Illuminate\Http\JsonResponse;

class PublicScanApiController extends Controller
{
    /**
     * GET /api/scan/{kode} & GET /api/public/scan/{kode}
     *
     * Endpoint publik khusus untuk pemindaian barcode barang.
     * Mengembalikan HANYA field yang benar-benar dibutuhkan untuk publik:
     * - kode_inventaris
     * - nama_barang
     * - spesifikasi
     * - lokasi (lokasi fisik penempatan aset)
     * - foto (URL foto utama)
     * - photos (daftar URL foto publik & caption)
     * - status_kepemilikan
     * - status_kontrak
     * - kontrak: tanggal_mulai, tanggal_berakhir, sisa_hari, status_kontrak
     *
     * KEAMANAN KETAT (DATA SENSITIF / INTERNAL TIDAK DITAMPILKAN):
     * - TIDAK menampilkan: nomor_seri
     * - TIDAK menampilkan: pic (dilindungi untuk admin internal)
     * - TIDAK menampilkan: vendor
     * - TIDAK menampilkan: biaya_sewa, total_biaya, atau data finansial apa pun
     * - TIDAK membocorkan field mentah internal database model.
     */
    public function show(string $kode): JsonResponse
    {
        $cleanKode = trim($kode);
        $inventaris = Inventaris::with(['kontrak', 'photos'])
            ->where('kode_inventaris', $cleanKode)
            ->first();

        if (!$inventaris) {
            return response()->json([
                'success' => false,
                'message' => 'Aset inventaris tidak ditemukan.',
                'kode' => $cleanKode,
            ], 404);
        }

        // Format foto publik (hanya URL dan caption, tanpa path server internal atau DB ID)
        $photos = collect($inventaris->all_photos)->map(function ($p) {
            return [
                'url' => $p['url'],
                'caption' => $p['caption'] ?? 'Foto Barang',
            ];
        })->values()->all();

        $mainFotoUrl = $inventaris->foto ? asset('storage/' . $inventaris->foto) : ($photos[0]['url'] ?? null);

        // Format data kontrak publik terbatas (tanpa vendor dan tanpa finansial)
        $kontrakData = null;
        if ($inventaris->kontrak) {
            $kontrakData = [
                'tanggal_mulai' => $inventaris->kontrak->tanggal_mulai?->format('Y-m-d'),
                'tanggal_berakhir' => $inventaris->kontrak->tanggal_berakhir?->format('Y-m-d'),
                'sisa_hari' => $inventaris->kontrak->sisa_hari,
                'status_kontrak' => $inventaris->kontrak->status_kontrak,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'kode_inventaris' => $inventaris->kode_inventaris,
                'nama_barang' => $inventaris->nama_barang,
                'spesifikasi' => $inventaris->spesifikasi,
                'lokasi' => $inventaris->lokasi,
                'foto' => $mainFotoUrl,
                'photos' => $photos,
                'status_kepemilikan' => $inventaris->status_kepemilikan,
                'status_kontrak' => $inventaris->status_kontrak,
                'kontrak' => $kontrakData,
            ],
        ]);
    }
}
