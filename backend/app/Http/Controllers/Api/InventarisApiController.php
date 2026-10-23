<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventaris;
use App\Models\InventoryPhoto;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InventarisApiController extends Controller
{
    /**
     * GET /api/inventaris
     * Menampilkan daftar inventaris dengan pencarian, filter, dan paginasi.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Inventaris::with(['kontrak', 'photos']);

        // Filter pencarian umum (search)
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_inventaris', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%")
                  ->orWhere('merek', 'like', "%{$search}%")
                  ->orWhere('tipe_model', 'like', "%{$search}%")
                  ->orWhere('nomor_seri', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhere('lokasi', 'like', "%{$search}%")
                  ->orWhere('pic', 'like', "%{$search}%")
                  ->orWhere('spesifikasi', 'like', "%{$search}%");
            });
        }

        // Filter kategori spesifik
        if ($kategori = $request->get('kategori')) {
            $query->where('kategori', $kategori);
        }

        // Filter vendor spesifik
        if ($vendor = $request->get('vendor')) {
            $query->where('vendor', $vendor);
        }

        // Filter lokasi spesifik
        if ($lokasi = $request->get('lokasi')) {
            $query->where('lokasi', $lokasi);
        }

        // Filter status fisik aset (status_aset)
        if ($statusAset = $request->get('status_aset')) {
            $query->where('status_aset', $statusAset);
        }

        // Filter status kontrak
        if ($statusKontrak = $request->get('status_kontrak')) {
            $today = Carbon::today();
            if (in_array($statusKontrak, ['AKTIF', 'Sedang Berjalan'])) {
                $query->whereHas('kontrak', function ($q) use ($today) {
                    $q->where('tanggal_berakhir', '>', $today->copy()->addDays(30));
                });
            } elseif (in_array($statusKontrak, ['AKAN BERAKHIR', 'Akan Berakhir'])) {
                $query->whereHas('kontrak', function ($q) use ($today) {
                    $q->whereBetween('tanggal_berakhir', [$today, $today->copy()->addDays(30)]);
                });
            } elseif (in_array($statusKontrak, ['BERAKHIR', 'Habis'])) {
                $query->whereHas('kontrak', function ($q) use ($today) {
                    $q->where('tanggal_berakhir', '<', $today);
                });
            } elseif ($statusKontrak === '-' || strtolower($statusKontrak) === 'tanpa kontrak') {
                $query->whereNull('kontrak_id');
            }
        }

        // Pengurutan (sorting)
        $sortBy = in_array($request->get('sort_by'), [
            'id', 'kode_inventaris', 'nama_barang', 'kategori', 'created_at', 'status_aset'
        ]) ? $request->get('sort_by') : 'id';

        $sortOrder = strtolower($request->get('sort_order')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Paginasi
        $perPage = min(max((int) $request->get('per_page', 10), 1), 100);
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(fn($inv) => $this->formatInventaris($inv));

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * GET /api/inventaris/{id}
     * Menampilkan detail satu inventaris berdasarkan ID atau Kode Inventaris.
     */
    public function show(string $id): JsonResponse
    {
        $inventaris = $this->findInventaris($id);

        if (!$inventaris) {
            return response()->json([
                'success' => false,
                'message' => 'Data inventaris tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatInventaris($inventaris),
        ]);
    }

    /**
     * POST /api/inventaris
     * Menambahkan data inventaris baru (dilindungi autentikasi).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_inventaris' => 'required|string|max:50|unique:inventaris,kode_inventaris',
            'nama_barang' => 'required|string|max:255',
            'kategori' => 'nullable|string|max:100',
            'merek' => 'nullable|string|max:100',
            'tipe_model' => 'nullable|string|max:100',
            'spesifikasi' => 'nullable|string',
            'nomor_seri' => 'nullable|string|max:100',
            'vendor' => 'nullable|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'pic' => 'nullable|string|max:255',
            'kondisi' => 'nullable|string|max:100',
            'status_aset' => 'required|in:Ada,Hilang,Rusak,Tidak Ditemukan',
            'keterangan' => 'nullable|string',
            'kontrak_id' => 'nullable|exists:kontrak,id',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $data = [
                'kode_inventaris' => strtoupper(trim($validated['kode_inventaris'])),
                'nama_barang' => $validated['nama_barang'],
                'kategori' => $validated['kategori'] ?? null,
                'merek' => $validated['merek'] ?? null,
                'tipe_model' => $validated['tipe_model'] ?? null,
                'spesifikasi' => $validated['spesifikasi'] ?? null,
                'nomor_seri' => $validated['nomor_seri'] ?? null,
                'vendor' => $validated['vendor'] ?? null,
                'lokasi' => $validated['lokasi'] ?? null,
                'pic' => $validated['pic'] ?? null,
                'kondisi' => $validated['kondisi'] ?? null,
                'status_aset' => $validated['status_aset'],
                'keterangan' => $validated['keterangan'] ?? null,
                'kontrak_id' => $validated['kontrak_id'] ?? null,
            ];

            // Upload foto utama lama jika ada
            if ($request->hasFile('foto')) {
                $data['foto'] = $request->file('foto')->store('inventaris', 'public');
            }

            $inventaris = Inventaris::create($data);

            // Upload foto jamak (multiple photos via inventory_photos table)
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $idx => $photoFile) {
                    $path = $photoFile->store('inventaris/photos', 'public');
                    InventoryPhoto::create([
                        'inventaris_id' => $inventaris->id,
                        'foto_path' => $path,
                        'keterangan_foto' => 'Foto Lampiran ' . ($idx + 1),
                    ]);
                }
            }

            $inventaris->load(['kontrak', 'photos']);

            return response()->json([
                'success' => true,
                'message' => 'Inventaris berhasil ditambahkan.',
                'data' => $this->formatInventaris($inventaris),
            ], 201);
        });
    }

    /**
     * PUT/PATCH /api/inventaris/{id}
     * Memperbarui data inventaris (dilindungi autentikasi).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $inventaris = $this->findInventaris($id);

        if (!$inventaris) {
            return response()->json([
                'success' => false,
                'message' => 'Data inventaris tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'kode_inventaris' => 'sometimes|required|string|max:50|unique:inventaris,kode_inventaris,' . $inventaris->id,
            'nama_barang' => 'sometimes|required|string|max:255',
            'kategori' => 'nullable|string|max:100',
            'merek' => 'nullable|string|max:100',
            'tipe_model' => 'nullable|string|max:100',
            'spesifikasi' => 'nullable|string',
            'nomor_seri' => 'nullable|string|max:100',
            'vendor' => 'nullable|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'pic' => 'nullable|string|max:255',
            'kondisi' => 'nullable|string|max:100',
            'status_aset' => 'sometimes|required|in:Ada,Hilang,Rusak,Tidak Ditemukan',
            'keterangan' => 'nullable|string',
            'kontrak_id' => 'nullable|exists:kontrak,id',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        return DB::transaction(function () use ($request, $validated, $inventaris) {
            $data = [];

            if (isset($validated['kode_inventaris'])) {
                $data['kode_inventaris'] = strtoupper(trim($validated['kode_inventaris']));
            }
            if (isset($validated['nama_barang'])) $data['nama_barang'] = $validated['nama_barang'];
            if (array_key_exists('kategori', $validated)) $data['kategori'] = $validated['kategori'];
            if (array_key_exists('merek', $validated)) $data['merek'] = $validated['merek'];
            if (array_key_exists('tipe_model', $validated)) $data['tipe_model'] = $validated['tipe_model'];
            if (array_key_exists('spesifikasi', $validated)) $data['spesifikasi'] = $validated['spesifikasi'];
            if (array_key_exists('nomor_seri', $validated)) $data['nomor_seri'] = $validated['nomor_seri'];
            if (array_key_exists('vendor', $validated)) $data['vendor'] = $validated['vendor'];
            if (array_key_exists('lokasi', $validated)) $data['lokasi'] = $validated['lokasi'];
            if (array_key_exists('pic', $validated)) $data['pic'] = $validated['pic'];
            if (array_key_exists('kondisi', $validated)) $data['kondisi'] = $validated['kondisi'];
            if (isset($validated['status_aset'])) $data['status_aset'] = $validated['status_aset'];
            if (array_key_exists('keterangan', $validated)) $data['keterangan'] = $validated['keterangan'];
            if (array_key_exists('kontrak_id', $validated)) $data['kontrak_id'] = $validated['kontrak_id'];

            // Ganti foto lama jika file baru diunggah
            if ($request->hasFile('foto')) {
                if ($inventaris->foto) {
                    Storage::disk('public')->delete($inventaris->foto);
                }
                $data['foto'] = $request->file('foto')->store('inventaris', 'public');
            }

            $inventaris->update($data);

            // Tambah foto jamak jika diunggah
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $idx => $photoFile) {
                    $path = $photoFile->store('inventaris/photos', 'public');
                    InventoryPhoto::create([
                        'inventaris_id' => $inventaris->id,
                        'foto_path' => $path,
                        'keterangan_foto' => 'Foto Tambahan ' . ($idx + 1),
                    ]);
                }
            }

            $inventaris->load(['kontrak', 'photos']);

            return response()->json([
                'success' => true,
                'message' => 'Inventaris berhasil diperbarui.',
                'data' => $this->formatInventaris($inventaris),
            ]);
        });
    }

    /**
     * DELETE /api/inventaris/{id}
     * Menghapus inventaris beserta file foto secara aman (dilindungi autentikasi).
     */
    public function destroy(string $id): JsonResponse
    {
        $inventaris = $this->findInventaris($id);

        if (!$inventaris) {
            return response()->json([
                'success' => false,
                'message' => 'Data inventaris tidak ditemukan.',
            ], 404);
        }

        return DB::transaction(function () use ($inventaris) {
            // Hapus file foto lama jika ada
            if ($inventaris->foto) {
                Storage::disk('public')->delete($inventaris->foto);
            }

            // Hapus seluruh file dari tabel inventory_photos
            foreach ($inventaris->photos as $p) {
                if ($p->foto_path) {
                    Storage::disk('public')->delete($p->foto_path);
                }
            }

            $inventaris->delete();

            return response()->json([
                'success' => true,
                'message' => 'Inventaris berhasil dihapus.',
            ]);
        });
    }

    /**
     * Helper untuk mencari inventaris berdasarkan ID atau Kode.
     */
    protected function findInventaris(string $id): ?Inventaris
    {
        return Inventaris::with(['kontrak', 'photos'])
            ->where('id', $id)
            ->orWhere('kode_inventaris', $id)
            ->first();
    }

    /**
     * Helper memformat data JSON inventaris secara aman dan rapi.
     */
    protected function formatInventaris(Inventaris $inv): array
    {
        return [
            'id' => $inv->id,
            'kode_inventaris' => $inv->kode_inventaris,
            'nama_barang' => $inv->nama_barang,
            'kategori' => $inv->kategori,
            'merek' => $inv->merek,
            'tipe_model' => $inv->tipe_model,
            'spesifikasi' => $inv->spesifikasi,
            'nomor_seri' => $inv->nomor_seri,
            'vendor' => $inv->vendor,
            'lokasi' => $inv->lokasi,
            'pic' => $inv->pic,
            'kondisi' => $inv->kondisi,
            'status_aset' => $inv->status_aset,
            'keterangan' => $inv->keterangan,
            'status_kepemilikan' => $inv->status_kepemilikan,
            'status_kontrak' => $inv->status_kontrak,
            'kontrak_id' => $inv->kontrak_id,
            'kontrak' => $inv->kontrak ? [
                'id' => $inv->kontrak->id,
                'kode_kontrak' => $inv->kontrak->kode_kontrak,
                'vendor' => $inv->kontrak->vendor,
                'tanggal_mulai' => $inv->kontrak->tanggal_mulai?->format('Y-m-d'),
                'tanggal_berakhir' => $inv->kontrak->tanggal_berakhir?->format('Y-m-d'),
                'sisa_hari' => $inv->kontrak->sisa_hari,
                'sisa_kontrak' => $inv->kontrak->sisa_kontrak,
                'status_kontrak' => $inv->kontrak->status_kontrak,
            ] : null,
            'photos' => $inv->all_photos,
            'barcode_url' => $inv->barcode_url,
            'created_at' => $inv->created_at?->toIso8601String(),
            'updated_at' => $inv->updated_at?->toIso8601String(),
        ];
    }

    /**
     * GET /api/inventaris/export/pdf
     */
    public function exportPdf(Request $request)
    {
        $query = Inventaris::with('kontrak');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_inventaris', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhere('lokasi', 'like', "%{$search}%");
            });
        }

        if ($sa = $request->get('status_aset')) {
            $query->where('status_aset', $sa);
        }
        if ($kat = $request->get('kategori')) {
            $query->where('kategori', $kat);
        }
        if ($v = $request->get('vendor')) {
            $query->where('vendor', $v);
        }
        if ($l = $request->get('lokasi')) {
            $query->where('lokasi', $l);
        }

        $inventarisList = $query->latest()->get();

        if ($sk = $request->get('status_kontrak')) {
            $inventarisList = $inventarisList->filter(function($i) use ($sk) {
                if ($sk === 'Sedang Berjalan' || $sk === 'AKTIF') return in_array($i->status_kontrak, ['AKTIF', 'Sedang Berjalan']);
                if ($sk === 'Akan Berakhir' || $sk === 'AKAN BERAKHIR') return in_array($i->status_kontrak, ['AKAN BERAKHIR', 'Akan Berakhir']);
                if ($sk === 'Habis' || $sk === 'BERAKHIR') return in_array($i->status_kontrak, ['BERAKHIR', 'Habis']);
                return $i->status_kontrak === $sk;
            })->values();
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.inventaris-pdf', [
            'inventaris' => $inventarisList,
            'title' => 'Laporan Inventaris',
            'tanggal' => Carbon::now()->isoFormat('D MMMM Y'),
            'filters' => $request->only(['search', 'status_aset', 'status_kontrak', 'kategori', 'vendor', 'lokasi']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('inventaris-' . date('Ymd') . '.pdf');
    }

    /**
     * GET /api/inventaris/export/excel
     */
    public function exportExcel(Request $request)
    {
        $query = Inventaris::with('kontrak');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_inventaris', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhere('lokasi', 'like', "%{$search}%");
            });
        }

        if ($sa = $request->get('status_aset')) {
            $query->where('status_aset', $sa);
        }
        if ($kat = $request->get('kategori')) {
            $query->where('kategori', $kat);
        }
        if ($v = $request->get('vendor')) {
            $query->where('vendor', $v);
        }
        if ($l = $request->get('lokasi')) {
            $query->where('lokasi', $l);
        }

        $inventarisList = $query->latest()->get();

        if ($sk = $request->get('status_kontrak')) {
            $inventarisList = $inventarisList->filter(function($i) use ($sk) {
                if ($sk === 'Sedang Berjalan' || $sk === 'AKTIF') return in_array($i->status_kontrak, ['AKTIF', 'Sedang Berjalan']);
                if ($sk === 'Akan Berakhir' || $sk === 'AKAN BERAKHIR') return in_array($i->status_kontrak, ['AKAN BERAKHIR', 'Akan Berakhir']);
                if ($sk === 'Habis' || $sk === 'BERAKHIR') return in_array($i->status_kontrak, ['BERAKHIR', 'Habis']);
                return $i->status_kontrak === $sk;
            })->values();
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\InventarisExport($inventarisList, 'Data Inventaris'),
            'inventaris-' . date('Ymd') . '.xlsx'
        );
    }
}
