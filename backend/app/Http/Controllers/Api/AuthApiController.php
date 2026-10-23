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
     * Terlindungi oleh validasi CSRF bawaan Laravel.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

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
     * Terlindungi oleh middleware 'auth'.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
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
