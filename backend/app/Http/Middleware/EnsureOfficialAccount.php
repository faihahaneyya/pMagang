<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOfficialAccount
{
    /**
     * Daftar akun resmi yang diizinkan mengakses seluruh menu internal sistem.
     * IT dan Finance memiliki hak akses setara terhadap seluruh modul internal.
     */
    public const ALLOWED_EMAILS = [
        'it@banyankoalindo.co.id',
        'finance@banyankoalindo.co.id',
    ];

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

        $email = strtolower(trim((string) $user->email));

        if (!in_array($email, self::ALLOWED_EMAILS, true)) {
            // Cabut sesi akun yang tidak diizinkan
            if ($request->hasSession()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
            }

            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya akun IT dan Finance resmi PT Banyan Koalindo Lestari yang memiliki hak akses ke sistem internal.',
            ], 403);
        }

        return $next($request);
    }
}
