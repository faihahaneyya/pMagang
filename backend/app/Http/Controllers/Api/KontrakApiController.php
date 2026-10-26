<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kontrak;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KontrakApiController extends Controller
{
    /**
     * GET /api/kontrak
     * Menampilkan daftar kontrak dengan pencarian, filter status, dan paginasi.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Kontrak::withCount('inventaris');

        // Pencarian umum
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_kontrak', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        // Filter status kontrak
        if ($status = $request->get('status_kontrak')) {
            $today = Carbon::today();
            if (in_array($status, ['AKTIF', 'Sedang Berjalan'])) {
                $query->where('tanggal_berakhir', '>', $today->copy()->addDays(30));
            } elseif (in_array($status, ['AKAN BERAKHIR', 'Akan Berakhir'])) {
                $query->whereBetween('tanggal_berakhir', [$today, $today->copy()->addDays(30)]);
            } elseif (in_array($status, ['BERAKHIR', 'Habis'])) {
                $query->where('tanggal_berakhir', '<', $today);
            }
        }

        // Pengurutan
        $sortBy = in_array($request->get('sort_by'), [
            'id', 'kode_kontrak', 'vendor', 'tanggal_mulai', 'tanggal_berakhir', 'biaya_sewa', 'created_at'
        ]) ? $request->get('sort_by') : 'tanggal_berakhir';

        $sortOrder = strtolower($request->get('sort_order')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Paginasi
        $perPage = min(max((int) $request->get('per_page', 10), 1), 100);
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(fn($k) => $this->formatKontrak($k));

        // Agregasi statistik KPI global dari seluruh data kontrak di database MySQL
        $allKontrak = Kontrak::all();
        $totalDokumen = $allKontrak->count();
        $kontrakAktif = $allKontrak->filter(fn($k) => in_array($k->status_kontrak, ['AKTIF', 'Sedang Berjalan']))->count();
        $kontrakAkanBerakhir = $allKontrak->filter(fn($k) => in_array($k->status_kontrak, ['AKAN BERAKHIR', 'Akan Berakhir']))->count();
        $kontrakBerakhir = $allKontrak->filter(fn($k) => in_array($k->status_kontrak, ['BERAKHIR', 'Habis']))->count();
        $akumulasiKomitmen = (float) $allKontrak->sum(fn($k) => (float) $k->total_biaya);

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'kpi' => [
                'total' => $totalDokumen,
                'aktif' => $kontrakAktif,
                'akan_berakhir' => $kontrakAkanBerakhir,
                'berakhir' => $kontrakBerakhir,
                'total_komitmen' => $akumulasiKomitmen,
            ],
        ]);
    }

    /**
     * GET /api/kontrak/{id}
     * Menampilkan detail kontrak beserta daftar inventaris yang terikat.
     */
    public function show(string $id): JsonResponse
    {
        $kontrak = $this->findKontrak($id);

        if (!$kontrak) {
            return response()->json([
                'success' => false,
                'message' => 'Data kontrak tidak ditemukan.',
            ], 404);
        }

        $kontrak->load('inventaris');

        return response()->json([
            'success' => true,
            'data' => $this->formatKontrakDetail($kontrak),
        ]);
    }

    /**
     * POST /api/kontrak
     * Menambahkan data kontrak baru (dilindungi autentikasi).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_kontrak' => 'required|string|max:50|unique:kontrak,kode_kontrak',
            'vendor' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_berakhir' => 'required|date|after_or_equal:tanggal_mulai',
            'biaya_sewa' => 'nullable|numeric|min:0',
            'durasi' => 'nullable|string|max:100',
            'keterangan' => 'nullable|string',
        ]);

        $kontrak = Kontrak::create([
            'kode_kontrak' => strtoupper(trim($validated['kode_kontrak'])),
            'vendor' => $validated['vendor'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_berakhir' => $validated['tanggal_berakhir'],
            'durasi' => $validated['durasi'] ?? null,
            'biaya_sewa' => $validated['biaya_sewa'] ?? 0,
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kontrak berhasil ditambahkan.',
            'data' => $this->formatKontrak($kontrak),
        ], 201);
    }

    /**
     * PUT/PATCH /api/kontrak/{id}
     * Memperbarui data kontrak (dilindungi autentikasi).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $kontrak = $this->findKontrak($id);

        if (!$kontrak) {
            return response()->json([
                'success' => false,
                'message' => 'Data kontrak tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'kode_kontrak' => 'sometimes|required|string|max:50|unique:kontrak,kode_kontrak,' . $kontrak->id,
            'vendor' => 'sometimes|required|string|max:255',
            'tanggal_mulai' => 'sometimes|required|date',
            'tanggal_berakhir' => 'sometimes|required|date|after_or_equal:tanggal_mulai',
            'biaya_sewa' => 'nullable|numeric|min:0',
            'durasi' => 'nullable|string|max:100',
            'keterangan' => 'nullable|string',
        ]);

        $data = [];
        if (isset($validated['kode_kontrak'])) $data['kode_kontrak'] = strtoupper(trim($validated['kode_kontrak']));
        if (isset($validated['vendor'])) $data['vendor'] = $validated['vendor'];
        if (isset($validated['tanggal_mulai'])) $data['tanggal_mulai'] = $validated['tanggal_mulai'];
        if (isset($validated['tanggal_berakhir'])) $data['tanggal_berakhir'] = $validated['tanggal_berakhir'];
        if (array_key_exists('biaya_sewa', $validated)) $data['biaya_sewa'] = $validated['biaya_sewa'] ?? 0;
        if (array_key_exists('durasi', $validated)) $data['durasi'] = $validated['durasi'];
        if (array_key_exists('keterangan', $validated)) $data['keterangan'] = $validated['keterangan'];

        $kontrak->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Kontrak berhasil diperbarui.',
            'data' => $this->formatKontrak($kontrak),
        ]);
    }

    /**
     * DELETE /api/kontrak/{id}
     * Menghapus data kontrak secara aman (dilindungi autentikasi).
     * Inventaris terkait dilepaskan (kontrak_id diset null) agar status kepemilikannya
     * secara otomatis beralih menjadi MILIK PERUSAHAAN tanpa data aset hilang.
     */
    public function destroy(string $id): JsonResponse
    {
        $kontrak = $this->findKontrak($id);

        if (!$kontrak) {
            return response()->json([
                'success' => false,
                'message' => 'Data kontrak tidak ditemukan.',
            ], 404);
        }

        return DB::transaction(function () use ($kontrak) {
            // Lepas keterikatan inventaris agar aset tidak terhapus
            $affected = $kontrak->inventaris()->update(['kontrak_id' => null]);

            $kontrak->delete();

            $message = 'Kontrak berhasil dihapus.';
            if ($affected > 0) {
                $message .= " {$affected} inventaris dialihkan ke kepemilikan mandiri.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        });
    }

    /**
     * Helper untuk mencari kontrak berdasarkan ID atau Kode.
     */
    protected function findKontrak(string $id): ?Kontrak
    {
        return Kontrak::where('id', $id)
            ->orWhere('kode_kontrak', $id)
            ->first();
    }

    /**
     * Helper memformat data JSON kontrak ringkas.
     */
    protected function formatKontrak(Kontrak $k): array
    {
        return [
            'id' => $k->id,
            'kode_kontrak' => $k->kode_kontrak,
            'vendor' => $k->vendor,
            'tanggal_mulai' => $k->tanggal_mulai?->format('Y-m-d'),
            'tanggal_berakhir' => $k->tanggal_berakhir?->format('Y-m-d'),
            'durasi' => $k->durasi,
            'biaya_sewa' => (float) $k->biaya_sewa,
            'total_biaya' => (float) $k->total_biaya,
            'sisa_hari' => $k->sisa_hari,
            'sisa_kontrak' => $k->sisa_kontrak,
            'status_kontrak' => $k->status_kontrak,
            'jumlah_inventaris' => $k->jumlah_inventaris,
            'keterangan' => $k->keterangan,
            'created_at' => $k->created_at?->toIso8601String(),
            'updated_at' => $k->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Helper memformat data JSON kontrak lengkap dengan inventaris terikat.
     */
    protected function formatKontrakDetail(Kontrak $k): array
    {
        $base = $this->formatKontrak($k);
        $base['inventaris'] = $k->inventaris->map(function ($inv) {
            return [
                'id' => $inv->id,
                'kode_inventaris' => $inv->kode_inventaris,
                'nama_barang' => $inv->nama_barang,
                'kategori' => $inv->kategori,
                'lokasi' => $inv->lokasi,
                'status_aset' => $inv->status_aset,
                'status_kepemilikan' => $inv->status_kepemilikan,
            ];
        });

        return $base;
    }

    /**
     * GET /api/kontrak/export/pdf
     */
    public function exportPdf(Request $request)
    {
        $kontrakList = Kontrak::withCount('inventaris')->get();

        if ($status = $request->get('status')) {
            $kontrakList = $kontrakList->filter(fn($k) => $k->status_kontrak === $status)->values();
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.kontrak-pdf', [
            'kontrakList' => $kontrakList,
            'title' => 'Laporan Kontrak',
            'tanggal' => Carbon::now()->isoFormat('D MMMM Y'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('kontrak-' . date('Ymd') . '.pdf');
    }

    /**
     * GET /api/kontrak/export/excel
     */
    public function exportExcel(Request $request)
    {
        $kontrakList = Kontrak::withCount('inventaris')->get();

        if ($status = $request->get('status')) {
            $kontrakList = $kontrakList->filter(fn($k) => $k->status_kontrak === $status)->values();
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\KontrakExport($kontrakList),
            'kontrak-' . date('Ymd') . '.xlsx'
        );
    }
}
