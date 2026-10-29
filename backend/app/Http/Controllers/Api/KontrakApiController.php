<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kontrak;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KontrakApiController extends Controller
{
    /**
     * GET /api/kontrak
     * Menampilkan daftar kontrak dengan pencarian, filter status, dan paginasi.
     * Akses: IT + KEUANGAN
     */
    public function index(Request $request): JsonResponse
    {
        $query = Kontrak::with('vendor');

        // Pencarian umum
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_kontrak', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%");
                  });
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
        $perPage   = min(max((int) $request->get('per_page', 10), 1), 100);
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(fn($k) => $this->formatKontrak($k));

        // KPI global
        $allKontrak        = Kontrak::all();
        $totalDokumen      = $allKontrak->count();
        $kontrakAktif      = $allKontrak->filter(fn($k) => in_array($k->status_kontrak, ['AKTIF']))->count();
        $kontrakAkanBerakhir = $allKontrak->filter(fn($k) => $k->status_kontrak === 'AKAN BERAKHIR')->count();
        $kontrakBerakhir   = $allKontrak->filter(fn($k) => $k->status_kontrak === 'BERAKHIR')->count();
        $akumulasiKomitmen = (float) $allKontrak->sum(fn($k) => (float) $k->total_biaya);

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            'kpi' => [
                'total'           => $totalDokumen,
                'aktif'           => $kontrakAktif,
                'akan_berakhir'   => $kontrakAkanBerakhir,
                'berakhir'        => $kontrakBerakhir,
                'total_komitmen'  => $akumulasiKomitmen,
            ],
        ]);
    }

    /**
     * GET /api/kontrak/{id}
     * Menampilkan detail kontrak beserta daftar inventaris melalui contract_items.
     * Akses: IT + KEUANGAN
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

        $kontrak->load(['vendor', 'inventarisItems']);

        return response()->json([
            'success' => true,
            'data'    => $this->formatKontrakDetail($kontrak),
        ]);
    }

    /**
     * POST /api/kontrak
     * Menambahkan data kontrak baru.
     * Akses: IT + KEUANGAN
     * Vendor dipilih dari tabel vendors (vendor_id wajib) — tidak membuat vendor baru otomatis.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_kontrak'    => 'required|string|max:50|unique:kontrak,kode_kontrak',
            'vendor_id'       => 'required|exists:vendors,id',
            'tanggal_mulai'   => 'required|date',
            'tanggal_berakhir'=> 'required|date|after_or_equal:tanggal_mulai',
            'biaya_sewa'      => 'nullable|numeric|min:0',
            'durasi'          => 'nullable|string|max:100',
            'keterangan'      => 'nullable|string',
        ]);

        $vendor  = Vendor::find($validated['vendor_id']);
        $kontrak = Kontrak::create([
            'kode_kontrak'    => strtoupper(trim($validated['kode_kontrak'])),
            'vendor_id'       => $validated['vendor_id'],
            'vendor'          => $vendor->name, // Sinkron kolom lama
            'tanggal_mulai'   => $validated['tanggal_mulai'],
            'tanggal_berakhir'=> $validated['tanggal_berakhir'],
            'durasi'          => $validated['durasi'] ?? null,
            'biaya_sewa'      => $validated['biaya_sewa'] ?? 0,
            'keterangan'      => $validated['keterangan'] ?? null,
        ]);

        $kontrak->load('vendor');

        return response()->json([
            'success' => true,
            'message' => 'Kontrak berhasil ditambahkan.',
            'data'    => $this->formatKontrak($kontrak),
        ], 201);
    }

    /**
     * PUT/PATCH /api/kontrak/{id}
     * Memperbarui data kontrak.
     * Akses: IT + KEUANGAN
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
            'kode_kontrak'    => 'sometimes|required|string|max:50|unique:kontrak,kode_kontrak,' . $kontrak->id,
            'vendor_id'       => 'sometimes|required|exists:vendors,id',
            'tanggal_mulai'   => 'sometimes|required|date',
            'tanggal_berakhir'=> 'sometimes|required|date|after_or_equal:tanggal_mulai',
            'biaya_sewa'      => 'nullable|numeric|min:0',
            'durasi'          => 'nullable|string|max:100',
            'keterangan'      => 'nullable|string',
        ]);

        $data = [];
        if (isset($validated['kode_kontrak']))    $data['kode_kontrak']    = strtoupper(trim($validated['kode_kontrak']));
        if (isset($validated['tanggal_mulai']))   $data['tanggal_mulai']   = $validated['tanggal_mulai'];
        if (isset($validated['tanggal_berakhir'])) $data['tanggal_berakhir'] = $validated['tanggal_berakhir'];
        if (array_key_exists('biaya_sewa', $validated))   $data['biaya_sewa']  = $validated['biaya_sewa'] ?? 0;
        if (array_key_exists('durasi', $validated))       $data['durasi']      = $validated['durasi'];
        if (array_key_exists('keterangan', $validated))   $data['keterangan']  = $validated['keterangan'];

        if (isset($validated['vendor_id'])) {
            $vendor           = Vendor::find($validated['vendor_id']);
            $data['vendor_id'] = $validated['vendor_id'];
            $data['vendor']    = $vendor->name; // Sinkron kolom lama
        }

        $kontrak->update($data);
        $kontrak->load('vendor');

        return response()->json([
            'success' => true,
            'message' => 'Kontrak berhasil diperbarui.',
            'data'    => $this->formatKontrak($kontrak),
        ]);
    }

    /**
     * DELETE /api/kontrak/{id}
     * Menghapus kontrak. Relasi contract_items dihapus cascade.
     * Inventaris dengan kontrak_id lama dilepas (set null).
     * Akses: IT only
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
            // Periksa apakah masih memiliki barang terkait dalam tabel contract_items
            $itemCount = \App\Models\ContractItem::where('contract_id', $kontrak->id)->count();
            if ($itemCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Kontrak '{$kontrak->kode_kontrak}' tidak dapat dihapus karena masih terhubung dengan {$itemCount} barang inventaris. Lepaskan seluruh barang dari kontrak terlebih dahulu.",
                ], 422);
            }

            // Lepas keterikatan inventaris lama (FK langsung kontrak_id) jika ada
            $kontrak->inventaris()->update(['kontrak_id' => null]);

            $kontrak->delete();

            return response()->json([
                'success' => true,
                'message' => "Kontrak '{$kontrak->kode_kontrak}' berhasil dihapus.",
            ]);
        });
    }

    /**
     * Helper untuk mencari kontrak berdasarkan ID atau Kode.
     */
    protected function findKontrak(string $id): ?Kontrak
    {
        return Kontrak::with('vendor')
            ->where('id', $id)
            ->orWhere('kode_kontrak', $id)
            ->first();
    }

    /**
     * Helper memformat data JSON kontrak ringkas.
     */
    protected function formatKontrak(Kontrak $k): array
    {
        $vendorRel = $k->relationLoaded('vendor') ? $k->getRelation('vendor') : ($k->vendor_id ? \App\Models\Vendor::find($k->vendor_id) : null);
        $rawVendor = $k->getRawOriginal('vendor') ?? ($k->attributes['vendor'] ?? null);
        $vendorName = is_object($vendorRel) ? $vendorRel->name : (is_string($rawVendor) && $rawVendor ? $rawVendor : ($vendorRel?->name ?? '-'));

        return [
            'id'              => $k->id,
            'kode_kontrak'    => $k->kode_kontrak,
            'vendor_id'       => $k->vendor_id,
            'vendor'          => $vendorName,
            'vendor_detail'   => is_object($vendorRel) ? [
                'id' => $vendorRel->id,
                'name' => $vendorRel->name,
                'email' => $vendorRel->email,
                'phone' => $vendorRel->phone,
            ] : null,
            'tanggal_mulai'   => $k->tanggal_mulai?->format('Y-m-d'),
            'tanggal_berakhir'=> $k->tanggal_berakhir?->format('Y-m-d'),
            'durasi'          => $k->durasi,
            'biaya_sewa'      => (float) $k->biaya_sewa,
            'total_biaya'     => (float) $k->total_biaya,
            'sisa_hari'       => $k->sisa_hari,
            'sisa_kontrak'    => $k->sisa_kontrak,
            'status_kontrak'  => $k->status_kontrak,
            'jumlah_inventaris' => $k->jumlah_inventaris,
            'keterangan'      => $k->keterangan,
            'created_at'      => $k->created_at?->toIso8601String(),
            'updated_at'      => $k->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Helper memformat data JSON kontrak lengkap dengan inventaris via contract_items.
     */
    protected function formatKontrakDetail(Kontrak $k): array
    {
        $base = $this->formatKontrak($k);
        $base['inventaris'] = $k->inventarisItems->map(function ($inv) {
            return [
                'id'               => $inv->id,
                'kode_inventaris'  => $inv->kode_inventaris,
                'nama_barang'      => $inv->nama_barang,
                'merek'            => $inv->merek,
                'tipe_model'       => $inv->tipe_model,
                'lokasi'           => $inv->lokasi,
                'status_aset'      => $inv->status_aset,
                'status_kepemilikan' => $inv->status_kepemilikan,
            ];
        });
        return $base;
    }

    /**
     * GET /api/kontrak/export/pdf
     * Akses: IT + KEUANGAN
     */
    public function exportPdf(Request $request)
    {
        $kontrakList = Kontrak::with('vendor')->get();

        if ($status = $request->get('status')) {
            $kontrakList = $kontrakList->filter(fn($k) => $k->status_kontrak === $status)->values();
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.kontrak-pdf', [
            'kontrakList' => $kontrakList,
            'title'       => 'Laporan Kontrak',
            'tanggal'     => Carbon::now()->isoFormat('D MMMM Y'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('kontrak-' . date('Ymd') . '.pdf');
    }

    /**
     * GET /api/kontrak/export/excel
     * Akses: IT + KEUANGAN
     */
    public function exportExcel(Request $request)
    {
        $kontrakList = Kontrak::with('vendor')->get();

        if ($status = $request->get('status')) {
            $kontrakList = $kontrakList->filter(fn($k) => $k->status_kontrak === $status)->values();
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\KontrakExport($kontrakList),
            'kontrak-' . date('Ymd') . '.xlsx'
        );
    }
}
