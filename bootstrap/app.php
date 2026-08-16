<?php

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SupabaseAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            'supabase.auth' => SupabaseAuth::class,
            'role' => RoleMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        /*
        |--------------------------------------------------------------------------
        | Validation Error
        |--------------------------------------------------------------------------
        */

        $exceptions->render(
            function (
                ValidationException $exception,
                Request $request
            ) {
                if (! $request->is('api/*')) {
                    return;
                }

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Data yang diberikan tidak valid.',
                        'errors' => $exception->errors(),
                    ],
                ], $exception->status);
            }
        );

        /*
        |--------------------------------------------------------------------------
        | HTTP Exceptions
        |--------------------------------------------------------------------------
        */

        $exceptions->render(
            function (
                HttpExceptionInterface $exception,
                Request $request
            ) {
                if (! $request->is('api/*')) {
                    return;
                }

                $status =
                    $exception->getStatusCode();

                [$code, $message] =
                    match ($status) {
                        400 => [
                            'BAD_REQUEST',
                            'Request tidak valid.',
                        ],

                        401 => [
                            'UNAUTHENTICATED',
                            'Autentikasi diperlukan.',
                        ],

                        403 => [
                            'FORBIDDEN',
                            'Anda tidak memiliki akses ke resource ini.',
                        ],

                        404 => [
                            'NOT_FOUND',
                            'Resource yang diminta tidak ditemukan.',
                        ],

                        405 => [
                            'METHOD_NOT_ALLOWED',
                            'Method HTTP tidak diizinkan untuk endpoint ini.',
                        ],

                        409 => [
                            'CONFLICT',
                            'Terjadi konflik pada request.',
                        ],

                        413 => [
                            'PAYLOAD_TOO_LARGE',
                            'Ukuran request terlalu besar.',
                        ],

                        415 => [
                            'UNSUPPORTED_MEDIA_TYPE',
                            'Format request tidak didukung.',
                        ],

                        422 => [
                            'UNPROCESSABLE_CONTENT',
                            'Request tidak dapat diproses.',
                        ],

                        429 => [
                            'TOO_MANY_REQUESTS',
                            'Terlalu banyak request. Silakan coba lagi nanti.',
                        ],

                        500 => [
                            'INTERNAL_SERVER_ERROR',
                            'Terjadi kesalahan pada server.',
                        ],

                        503 => [
                            'SERVICE_UNAVAILABLE',
                            'Layanan sedang tidak tersedia.',
                        ],

                        default => [
                            'HTTP_ERROR',
                            'Terjadi kesalahan saat memproses request.',
                        ],
                    };

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => $code,
                        'message' => $message,
                    ],
                ], $status, $exception->getHeaders());
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Unhandled Server Error
        |--------------------------------------------------------------------------
        */

        $exceptions->render(
            function (
                Throwable $exception,
                Request $request
            ) {
                if (! $request->is('api/*')) {
                    return;
                }

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INTERNAL_SERVER_ERROR',
                        'message' => 'Terjadi kesalahan pada server.',
                    ],
                ], 500);
            }
        );

    })
    ->create();
