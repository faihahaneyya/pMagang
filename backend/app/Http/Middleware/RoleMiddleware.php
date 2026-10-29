<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware RBAC — memeriksa role pengguna yang sedang login.
 * Digunakan sebagai: Route::middleware('role:IT') atau Route::middleware('role:IT,KEUANGAN')
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userRole = strtoupper(trim((string) $user->role));
        $allowedRoles = array_map('strtoupper', $roles);

        if (!in_array($userRole, $allowedRoles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki hak akses untuk fitur ini.',
                'required_roles' => $allowedRoles,
                'your_role' => $userRole,
            ], 403);
        }

        return $next($request);
    }
}
