<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorApiController extends Controller
{
    /**
     * GET /api/vendor
     * Menampilkan daftar vendor dengan pencarian dan paginasi.
     * Akses: IT + KEUANGAN
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vendor::withCount('kontraks');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $sortBy    = in_array($request->get('sort_by'), ['id', 'name', 'created_at']) ? $request->get('sort_by') : 'name';
        $sortOrder = strtolower($request->get('sort_order')) === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage   = min(max((int) $request->get('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(fn($v) => $this->formatVendor($v));

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * GET /api/vendor/all
     * Mengambil semua vendor tanpa paginasi (untuk dropdown).
     */
    public function all(Request $request): JsonResponse
    {
        $vendors = Vendor::orderBy('name')->get(['id', 'name', 'email', 'phone']);
        return response()->json([
            'success' => true,
            'data'    => $vendors,
        ]);
    }

    /**
     * GET /api/vendor/{id}
     * Menampilkan detail vendor.
     * Akses: IT + KEUANGAN
     */
    public function show(string $id): JsonResponse
    {
        $vendor = Vendor::with('kontraks')->find($id);

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatVendorDetail($vendor),
        ]);
    }

    /**
     * POST /api/vendor
     * Menambahkan vendor baru.
     * Akses: IT + KEUANGAN
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone'   => 'nullable|string|max:50',
            'email'   => 'nullable|email|max:255',
        ]);

        $vendor = Vendor::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vendor berhasil ditambahkan.',
            'data'    => $this->formatVendor($vendor),
        ], 201);
    }

    /**
     * PUT/PATCH /api/vendor/{id}
     * Memperbarui data vendor.
     * Akses: IT + KEUANGAN
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'name'    => 'sometimes|required|string|max:255',
            'address' => 'nullable|string',
            'phone'   => 'nullable|string|max:50',
            'email'   => 'nullable|email|max:255',
        ]);

        $vendor->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vendor berhasil diperbarui.',
            'data'    => $this->formatVendor($vendor),
        ]);
    }

    /**
     * DELETE /api/vendor/{id}
     * Menghapus vendor. Tidak diizinkan jika masih memiliki kontrak aktif.
     * Akses: IT only
     */
    public function destroy(string $id): JsonResponse
    {
        $vendor = Vendor::withCount('kontraks')->find($id);

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor tidak ditemukan.',
            ], 404);
        }

        if ($vendor->kontraks_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Vendor tidak dapat dihapus karena masih memiliki {$vendor->kontraks_count} kontrak terkait. Hapus atau alihkan kontrak terlebih dahulu.",
            ], 422);
        }

        $vendor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vendor berhasil dihapus.',
        ]);
    }

    protected function formatVendor(Vendor $v): array
    {
        return [
            'id'              => $v->id,
            'name'            => $v->name,
            'address'         => $v->address,
            'phone'           => $v->phone,
            'email'           => $v->email,
            'jumlah_kontrak'  => $v->kontraks_count ?? $v->jumlah_kontrak,
            'created_at'      => $v->created_at?->toIso8601String(),
            'updated_at'      => $v->updated_at?->toIso8601String(),
        ];
    }

    protected function formatVendorDetail(Vendor $v): array
    {
        $base = $this->formatVendor($v);
        $base['kontraks'] = $v->kontraks->map(function ($k) {
            return [
                'id'             => $k->id,
                'kode_kontrak'   => $k->kode_kontrak,
                'tanggal_mulai'  => $k->tanggal_mulai?->format('Y-m-d'),
                'tanggal_berakhir' => $k->tanggal_berakhir?->format('Y-m-d'),
                'status_kontrak' => $k->status_kontrak,
                'biaya_sewa'     => (float) $k->biaya_sewa,
            ];
        });
        return $base;
    }
}
