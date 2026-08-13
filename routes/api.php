<?php

use App\Http\Controllers\Api\V1\Student\OrderController;
use App\Http\Controllers\Api\V1\Student\WalletController;
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

});