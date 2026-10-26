<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthApiController extends Controller
{
    /**
     * Endpoint inisiasi cookie sesi dan token CSRF untuk Frontend SPA (React).
     * Endpoint ini dijalankan di bawah middleware 'web', sehingga Laravel otomatis
     * melampirkan cookie 'XSRF-TOKEN' dan 'laravel_session' pada respons HTTP.
     */
    public function csrfCookie(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'CSRF cookie set successfully.',
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * Autentikasi admin menggunakan mekanisme sesi & cookie bawaan Laravel.
     * Terlindungi oleh validasi CSRF bawaan Laravel dan pembatasan akun resmi.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim($credentials['email']));

        // Batasan Keamanan Resmi PT BKL:
        // Hanya dua akun resmi (IT dan Finance) yang diizinkan untuk login ke sistem.
        if (!in_array($email, \App\Http\Middleware\EnsureOfficialAccount::ALLOWED_EMAILS, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak diizinkan untuk mengakses sistem. Hanya akun IT dan Finance resmi PT Banyan Koalindo Lestari yang memiliki hak akses.',
            ], 403);
        }

        $remember = (bool) $request->boolean('remember', false);

        if (Auth::guard('web')->attempt($credentials, $remember)) {
            // Mencegah Session Fixation attack dengan regenerasi ID sesi & token CSRF
            $request->session()->regenerate();

            /** @var \App\Models\User $user */
            $user = Auth::guard('web')->user();

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role ?? 'IT',
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Email atau kata sandi tidak valid.',
        ], 401);
    }

    /**
     * Mengembalikan profil pengguna yang sedang memiliki sesi aktif.
     * Terlindungi oleh middleware 'auth' dan pembatasan akun resmi.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $email = strtolower(trim((string) $user->email));
        if (!in_array($email, \App\Http\Middleware\EnsureOfficialAccount::ALLOWED_EMAILS, true)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Sesi tidak valid untuk akun ini.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'IT',
            ],
        ]);
    }

    /**
     * Mengakhiri sesi pengguna aktif dan meregenerasi token CSRF.
     * Terlindungi oleh middleware 'auth' dan validasi CSRF.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }
}
