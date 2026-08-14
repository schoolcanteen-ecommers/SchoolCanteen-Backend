<?php

namespace App\Http\Middleware;

use App\Models\Profile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class SupabaseAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Access token tidak ditemukan.',
                ],
            ], 401);
        }

        $supabaseUrl = config('services.supabase.url');
        $anonKey = config('services.supabase.anon_key');

        if (!$supabaseUrl || !$anonKey) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_CONFIGURATION_ERROR',
                    'message' => 'Konfigurasi Supabase Auth belum tersedia.',
                ],
            ], 500);
        }

        $response = Http::withHeaders([
            'apikey' => $anonKey,
            'Authorization' => 'Bearer '.$token,
        ])->get(
            rtrim($supabaseUrl, '/').'/auth/v1/user'
        );

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => 'Access token tidak valid atau sudah kedaluwarsa.',
                ],
            ], 401);
        }

        $supabaseUser = $response->json();

        $profile = Profile::find($supabaseUser['id']);

        if (!$profile) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PROFILE_NOT_FOUND',
                    'message' => 'Profile pengguna tidak ditemukan.',
                ],
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Simpan user ke request
        |--------------------------------------------------------------------------
        */

        $request->attributes->set(
            'supabase_user',
            $supabaseUser
        );

        $request->attributes->set(
            'profile',
            $profile
        );

        return $next($request);
    }
}