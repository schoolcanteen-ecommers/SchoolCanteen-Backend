<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminMerchantTransactionResource;
use App\Http\Resources\AdminStudentTransactionResource;
use App\Models\MerchantWalletTransaction;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function studentIndex(Request $request)
    {
        $query = WalletTransaction::query()
            ->with([
                'wallet.user:id,name',
                'paymentTransaction',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Search Student
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->query('search')
            );

            $query->whereHas(
                'wallet.user',
                function ($userQuery) use ($search) {
                    $userQuery->where(
                        'name',
                        'ilike',
                        '%' . $search . '%'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Student Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('student_id')) {
            $studentId =
                $request->query('student_id');

            $query->whereHas(
                'wallet',
                function ($walletQuery) use ($studentId) {
                    $walletQuery->where(
                        'user_id',
                        $studentId
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Transaction Filters
        |--------------------------------------------------------------------------
        */

        if ($request->filled('type')) {
            $query->where(
                'type',
                $request->query('type')
            );
        }

        if ($request->filled('direction')) {
            $query->where(
                'direction',
                $request->query('direction')
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->query('status')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Date Filters
        |--------------------------------------------------------------------------
        */

        if ($request->filled('date_from')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->query('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->query('date_to')
            );
        }

        $transactions = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return AdminStudentTransactionResource::collection(
            $transactions
        )->additional([
            'success' => true,
        ]);
    }

    public function studentShow(string $transaction)
    {
        $walletTransaction =
            WalletTransaction::query()
                ->with([
                    'wallet.user:id,name',
                    'paymentTransaction',
                ])
                ->find($transaction);

        if (!$walletTransaction) {
            return response()->json([
                'success' => false,

                'error' => [
                    'code' =>
                        'STUDENT_TRANSACTION_NOT_FOUND',

                    'message' =>
                        'Transaksi student tidak ditemukan.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,

            'data' =>
                new AdminStudentTransactionResource(
                    $walletTransaction
                ),
        ]);
    }

    public function merchantIndex(Request $request)
    {
        $query =
            MerchantWalletTransaction::query()
                ->with([
                    'merchantWallet.merchant:id,name,type',
                ]);

        /*
        |--------------------------------------------------------------------------
        | Search Merchant
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->query('search')
            );

            $query->whereHas(
                'merchantWallet.merchant',
                function ($merchantQuery) use ($search) {
                    $merchantQuery->where(
                        'name',
                        'ilike',
                        '%' . $search . '%'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Merchant Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('merchant_id')) {
            $merchantId =
                $request->query('merchant_id');

            $query->whereHas(
                'merchantWallet',
                function ($walletQuery) use ($merchantId) {
                    $walletQuery->where(
                        'merchant_id',
                        $merchantId
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Transaction Filters
        |--------------------------------------------------------------------------
        */

        if ($request->filled('type')) {
            $query->where(
                'type',
                $request->query('type')
            );
        }

        if ($request->filled('direction')) {
            $query->where(
                'direction',
                $request->query('direction')
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->query('status')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Date Filters
        |--------------------------------------------------------------------------
        */

        if ($request->filled('date_from')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->query('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->query('date_to')
            );
        }

        $transactions = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return AdminMerchantTransactionResource::collection(
            $transactions
        )->additional([
            'success' => true,
        ]);
    }

    public function merchantShow(string $transaction)
    {
        $merchantTransaction =
            MerchantWalletTransaction::query()
                ->with([
                    'merchantWallet.merchant:id,name,type',
                ])
                ->find($transaction);

        if (!$merchantTransaction) {
            return response()->json([
                'success' => false,

                'error' => [
                    'code' =>
                        'MERCHANT_TRANSACTION_NOT_FOUND',

                    'message' =>
                        'Transaksi merchant tidak ditemukan.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,

            'data' =>
                new AdminMerchantTransactionResource(
                    $merchantTransaction
                ),
        ]);
    }
}