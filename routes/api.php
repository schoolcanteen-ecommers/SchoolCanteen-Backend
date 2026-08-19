<?php

use App\Http\Controllers\Api\V1\PickupSlotController;
use App\Http\Controllers\Api\V1\MerchantController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\MidtransWebhookController;
use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\MerchantController as AdminMerchantController;
use App\Http\Controllers\Api\V1\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Api\V1\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Api\V1\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\Api\V1\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\V1\Student\OrderController;
use App\Http\Controllers\Api\V1\Merchant\OrderController as MerchantOrderController;
use App\Http\Controllers\Api\V1\Merchant\ProductionSummaryController;
use App\Http\Controllers\Api\V1\Merchant\PickupController as MerchantPickupController;
use App\Http\Controllers\Api\V1\Merchant\WalletController as MerchantWalletController;
use App\Http\Controllers\Api\V1\Merchant\PaymentAccountController as MerchantPaymentAccountController;
use App\Http\Controllers\Api\V1\Merchant\WithdrawalController as MerchantWithdrawalController;
use App\Http\Controllers\Api\V1\Merchant\ProductController as MerchantProductController;
use App\Http\Controllers\Api\V1\Merchant\CategoryController as MerchantCategoryController;
use App\Http\Controllers\Api\V1\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Api\V1\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Api\V1\Student\WalletController;
use App\Http\Controllers\Api\V1\Student\TopUpController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'SchoolCanteen API is running',
        'version' => 'v1',
    ]);
});


/*
|--------------------------------------------------------------------------
| API Version 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public API
    |--------------------------------------------------------------------------
    */

    Route::get('/products', [
        ProductController::class,
        'index',
    ]);

    Route::post('/products/resolve', [
        ProductController::class,
        'resolve',
    ]);

    Route::get('/products/{product}', [
        ProductController::class,
        'show',
    ])->whereUuid('product');

    Route::get('/merchants', [
        MerchantController::class,
        'index',
    ]);

    Route::get('/merchants/{merchant}', [
        MerchantController::class,
        'show',
    ])->whereUuid('merchant');

    Route::get('/merchants/{merchant}/pickup-slots', [
        PickupSlotController::class,
        'index',
    ])->whereUuid('merchant');

    Route::post('/payments/midtrans/notification', [
        MidtransWebhookController::class,
        'handle',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Authenticated API
    |--------------------------------------------------------------------------
    */

    Route::middleware('supabase.auth')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Current User
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Student
        |--------------------------------------------------------------------------
        */

        Route::prefix('student')
            ->middleware('role:student')
            ->group(function () {

            Route::get('/orders', [
                OrderController::class,
                'index',
            ]);

            Route::get('/orders/{order}', [
                OrderController::class,
                'show',
            ])->whereUuid('order');

            Route::post('/orders', [
                OrderController::class,
                'store',
            ]);

                Route::get('/test', function () {
                    return response()->json([
                        'success' => true,
                        'message' => 'Student access granted.',
                    ]);
                });

                Route::get('/wallet', [
                    WalletController::class,
                    'show',
                ]);

                Route::get('/wallet/transactions', [
                    WalletController::class,
                    'transactions',
                ]);

            Route::get('/dashboard', [
                StudentDashboardController::class,
                'index',
            ]);

            Route::get('/profile', [
                StudentProfileController::class,
                'show',
            ]);

            Route::post('/wallet/top-ups', [
                TopUpController::class,
                'store',
            ]);
            });


        /*
        |--------------------------------------------------------------------------
        | Merchant
        |--------------------------------------------------------------------------
        */

        Route::prefix('merchant')
            ->middleware('role:merchant')
            ->group(function () {

                Route::get('/orders', [
                    MerchantOrderController::class,
                    'index',
                ]);

                Route::get('/orders/{order}', [
                    MerchantOrderController::class,
                    'show',
                ])->whereUuid('order');

                Route::patch('/orders/{order}/status', [
                    MerchantOrderController::class,
                    'updateStatus',
                ])->whereUuid('order');

                Route::get('/production-summary', [
                    ProductionSummaryController::class,
                    'index',
                ]);

                    Route::post('/pickups/verify', [
                        MerchantPickupController::class,
                        'verify',
                    ]);

                Route::get('/wallet', [
                    MerchantWalletController::class,
                    'show',
                ]);

                Route::get('/wallet/transactions', [
                    MerchantWalletController::class,
                    'transactions',
                ]);

                Route::get('/payment-accounts', [
                    MerchantPaymentAccountController::class,
                    'index',
                ]);

                Route::post('/payment-accounts', [
                    MerchantPaymentAccountController::class,
                    'store',
                ]);

                Route::get('/withdrawals', [
                    MerchantWithdrawalController::class,
                    'index',
                ]);

                Route::post('/withdrawals', [
                    MerchantWithdrawalController::class,
                    'store',
                ]);

                Route::get('/products', [
                    MerchantProductController::class,
                    'index',
                ]);

                Route::post('/products', [
                    MerchantProductController::class,
                    'store',
                ]);

                Route::get('/categories', [
                    MerchantCategoryController::class,
                    'index',
                ]);

                Route::patch('/products/{product}', [
                    MerchantProductController::class,
                    'update',
                ])->whereUuid('product');

                Route::delete('/products/{product}', [
                    MerchantProductController::class,
                    'destroy',
                ])->whereUuid('product');

                Route::get('/test', function () {
                    return response()->json([
                        'success' => true,
                        'message' => 'Merchant access granted.',
                    ]);
                });
            });


        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        */

        Route::prefix('admin')
            ->middleware('role:admin')
            ->group(function () {

               Route::get('/dashboard', [
                        AdminDashboardController::class,
                        'index',
                    ]);

                Route::get('/merchants', [
                    AdminMerchantController::class,
                    'index',
                ]);

                Route::get('/merchants/{merchant}', [
                    AdminMerchantController::class,
                    'show',
                ])->whereUuid('merchant');

                Route::patch('/merchants/{merchant}/status', [
                    AdminMerchantController::class,
                    'updateStatus',
                ])->whereUuid('merchant');

                Route::get('/students', [
                    AdminStudentController::class,
                    'index',
                ]);

                Route::get('/students/{student}', [
                    AdminStudentController::class,
                    'show',
                ])->whereUuid('student');

                Route::get('/transactions/student', [
                    AdminTransactionController::class,
                    'studentIndex',
                ]);

                Route::get('/transactions/student/{transaction}', [
                    AdminTransactionController::class,
                    'studentShow',
                ])->whereUuid('transaction');

                Route::get('/transactions/merchant', [
                    AdminTransactionController::class,
                    'merchantIndex',
                ]);

                Route::get('/transactions/merchant/{transaction}', [
                    AdminTransactionController::class,
                    'merchantShow',
                ])->whereUuid('transaction');

                Route::get('/withdrawals', [
                    AdminWithdrawalController::class,
                    'index',
                ]);

                Route::get('/withdrawals/{withdrawal}', [
                    AdminWithdrawalController::class,
                    'show',
                ])->whereUuid('withdrawal');

                Route::patch('/withdrawals/{withdrawal}/approve', [
                    AdminWithdrawalController::class,
                    'approve',
                ])->whereUuid('withdrawal');

                Route::patch('/withdrawals/{withdrawal}/process', [
                    AdminWithdrawalController::class,
                    'process',
                ])->whereUuid('withdrawal');

                Route::patch('/withdrawals/{withdrawal}/complete', [
                    AdminWithdrawalController::class,
                    'complete',
                ])->whereUuid('withdrawal');

                Route::patch('/withdrawals/{withdrawal}/reject', [
                    AdminWithdrawalController::class,
                    'reject',
                ])->whereUuid('withdrawal');

                Route::get('/reports/summary', [
                    AdminReportController::class,
                    'summary',
                ]);

                Route::get('/test', function () {
                        return response()->json([
                            'success' => true,
                            'message' => 'Admin access granted.',
                    ]);
                });
            });
    });
});