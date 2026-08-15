<?php

use App\Http\Controllers\Api\V1\MerchantController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\Student\OrderController;
use App\Http\Controllers\Api\V1\Student\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/* Health Check */

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'SchoolCanteen API is running',
        'version' => 'v1',
    ]);
});


/* API Version 1 */

Route::prefix('v1')->group(function () {

    /* Public API */

    Route::get('/products', [
        ProductController::class,
        'index',
    ]);

    Route::get('/products/{product}', [
        ProductController::class,
        'show',
    ]);

    Route::get('/merchants', [
        MerchantController::class,
        'index',
    ]);

    Route::get('/merchants/{merchant}', [
        MerchantController::class,
        'show',
    ]);


    /* Authenticated API */

    Route::middleware('supabase.auth')
        ->group(function () {

            /* Student */

            Route::prefix('student')
                ->middleware('role:student')
                ->group(function () {

                    Route::get('/wallet', [
                        WalletController::class,
                        'show',
                    ]);

                    Route::get('/wallet/transactions', [
                        WalletController::class,
                        'transactions',
                    ]);

                });


            /* Merchant */

            Route::prefix('merchant')
                ->middleware('role:merchant')
                ->group(function () {

                    Route::get('/test', function () {
                        return response()->json([
                            'success' => true,
                            'message' => 'Merchant access granted.',
                        ]);
                    });

                });


            /* Admin */

            Route::prefix('admin')
                ->middleware('role:admin')
                ->group(function () {

                    Route::get('/test', function () {
                        return response()->json([
                            'success' => true,
                            'message' => 'Admin access granted.',
                        ]);
                    });

                });


            /* Current User */

            Route::get('/me', function (Request $request) {

                $profile = $request->attributes->get('profile');

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $profile->id,
                        'name' => $profile->name,
                        'phone' => $profile->phone,
                        'avatar_url' => $profile->avatar_url,
                        'role' => $profile->role,
                    ],
                ]);
            });

        });

});