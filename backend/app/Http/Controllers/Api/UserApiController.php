<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Manajemen akun pengguna — hanya dapat diakses oleh role IT.
 */
class UserApiController extends Controller
{
    /**
     * GET /api/users
     * Daftar semua pengguna.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->select(['id', 'name', 'email', 'role', 'created_at', 'updated_at']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->get('role')) {
            $query->where('role', strtoupper($role));
        }

        $users = $query->orderBy('name')->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $users->items(),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    /**
     * GET /api/users/{id}
     * Detail pengguna.
     */
    public function show(string $id): JsonResponse
    {
        $user = User::select(['id', 'name', 'email', 'role', 'created_at', 'updated_at'])->find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        return response()->json(['success' => true, 'data' => $user]);
    }

    /**
     * POST /api/users
     * Tambah pengguna baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'role'     => 'required|in:IT,KEUANGAN',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => strtoupper($validated['role']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil ditambahkan.',
            'data'    => $user->only(['id', 'name', 'email', 'role', 'created_at']),
        ], 201);
    }

    /**
     * PUT/PATCH /api/users/{id}
     * Perbarui data pengguna.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'name'     => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
            'password' => ['sometimes', 'nullable', Password::min(8)->letters()->numbers()],
            'role'     => 'sometimes|required|in:IT,KEUANGAN',
        ]);

        $data = [];
        if (isset($validated['name']))  $data['name']  = $validated['name'];
        if (isset($validated['email'])) $data['email'] = $validated['email'];
        if (isset($validated['role']))  $data['role']  = strtoupper($validated['role']);
        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil diperbarui.',
            'data'    => $user->fresh()->only(['id', 'name', 'email', 'role', 'updated_at']),
        ]);
    }

    /**
     * DELETE /api/users/{id}
     * Hapus pengguna. Tidak bisa menghapus diri sendiri.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        // Tidak boleh menghapus akun sendiri
        if ($request->user() && $request->user()->id == $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus akun pengguna yang sedang aktif digunakan saat ini.',
            ], 422);
        }

        // Perlindungan: Tidak boleh menghapus satu-satunya administrator IT
        if ($user->role === 'IT' && User::where('role', 'IT')->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus akun IT Administrator terakhir dalam sistem.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }
}
