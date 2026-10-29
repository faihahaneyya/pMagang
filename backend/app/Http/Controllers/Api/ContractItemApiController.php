<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContractItem;
use App\Models\Kontrak;
use App\Models\Inventaris;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractItemApiController extends Controller
{
    /**
     * GET /api/kontrak/{id}/items
     * Daftar inventaris dalam kontrak.
     * Akses: IT + KEUANGAN
     */
    public function index(string $contractId): JsonResponse
    {
        $kontrak = Kontrak::find($contractId);
        if (!$kontrak) {
            return response()->json(['success' => false, 'message' => 'Kontrak tidak ditemukan.'], 404);
        }

        $items = ContractItem::with(['inventaris' => function ($q) {
            $q->with('photos');
        }])->where('contract_id', $kontrak->id)->get()->map(function ($ci) {
            $inv = $ci->inventaris;
            return [
                'id'               => $ci->id, // ID unik Contract Item (Primary Key)
                'contract_item_id' => $ci->id,
                'contract_id'      => $ci->contract_id,
                'inventaris_id'    => $ci->inventaris_id,
                'kode_inventaris'  => $inv?->kode_inventaris ?? '-',
                'nama_barang'      => $inv?->nama_barang ?? '-',
                'merek'            => $inv?->merek ?? '-',
                'tipe_model'       => $inv?->tipe_model ?? '-',
                'lokasi'           => $inv?->lokasi ?? '-',
                'pic'              => $inv?->pic ?? '-',
                'status_aset'      => $inv?->status_aset ?? 'Ada',
                'inventaris'       => $inv,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $items,
            'total'   => $items->count(),
        ]);
    }

    /**
     * POST /api/kontrak/{id}/items
     * Tambahkan inventaris ke kontrak (via contract_items).
     * Akses: IT
     */
    public function store(Request $request, string $contractId): JsonResponse
    {
        $kontrak = Kontrak::find($contractId);
        if (!$kontrak) {
            return response()->json(['success' => false, 'message' => 'Kontrak tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'inventory_id'   => 'nullable|integer|exists:inventaris,id',
            'inventaris_id'  => 'nullable|integer|exists:inventaris,id',
        ]);

        $inventarisId = $validated['inventory_id'] ?? $validated['inventaris_id'] ?? null;
        if (!$inventarisId) {
            return response()->json([
                'success' => false,
                'message' => 'Kolom inventory_id atau inventaris_id wajib disertakan.',
                'errors'  => ['inventory_id' => ['ID inventaris wajib disertakan.']],
            ], 422);
        }

        // Cek duplikat
        $exists = ContractItem::where('contract_id', $kontrak->id)
            ->where('inventaris_id', $inventarisId)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Inventaris sudah terdaftar dalam kontrak ini.',
            ], 422);
        }

        $item = ContractItem::create([
            'contract_id'   => $kontrak->id,
            'inventaris_id' => $inventarisId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Inventaris berhasil ditambahkan ke kontrak.',
            'data'    => $item,
        ], 201);
    }

    /**
     * DELETE /api/kontrak/{id}/items/{itemId}
     * Lepas relasi inventaris dari kontrak.
     * Akses: IT
     */
    /**
     * DELETE /api/kontrak/{contractId}/items/{itemId}
     * Lepas relasi item inventaris dari kontrak secara spesifik.
     * Hanya menghapus baris relasi di contract_items, data inventaris fisik tetap aman.
     * Akses: IT
     */
    public function destroy(string $contractId, string $itemId): JsonResponse
    {
        // Cari record relasi unik berdasarkan contract_id dan primary key contract_item_id
        $item = ContractItem::where('contract_id', $contractId)
            ->where('id', $itemId)
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Relasi barang dalam kontrak (Contract Item) tidak ditemukan.',
            ], 404);
        }

        // Hapus HANYA relasi pivot (data inventaris fisik tetap utuh)
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil dilepas dari kontrak (data inventaris fisik tetap aman).',
        ]);
    }
}
