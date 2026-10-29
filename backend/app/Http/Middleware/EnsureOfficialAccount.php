<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: Memastikan pengguna yang login adalah akun resmi dengan role IT atau KEUANGAN.
 * Role USER diizinkan login tetapi TIDAK boleh mengakses endpoint admin ini.
 */
class EnsureOfficialAccount
{
    /**
     * Role yang diizinkan mengakses endpoint administrasi internal.
     */
    public const ADMIN_ROLES = ['IT', 'KEUANGAN'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userRole = strtoupper(trim((string) $user->role));

        if (!in_array($userRole, self::ADMIN_ROLES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Halaman administrasi hanya dapat diakses oleh akun IT dan Keuangan.',
                'redirect' => '/pindai',
            ], 403);
        }

        return $next($request);
    }
}
