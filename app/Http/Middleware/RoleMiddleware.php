<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {

        $profile = $request->attributes->get('profile');

        if (!$profile) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Profile pengguna tidak tersedia.',
                ],
            ], 401);
        }

        if (!in_array($profile->role, $roles, true)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Anda tidak memiliki akses ke resource ini.',
                ],
            ], 403);
        }

        return $next($request);
    }
}