<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Http\Resources\WalletTransactionResource;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request)
    {
        $profile = $request->attributes->get('profile');

        $wallet = Wallet::query()
            ->where('user_id', $profile->id)
            ->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WALLET_NOT_FOUND',
                    'message' => 'Wallet pengguna tidak ditemukan.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new WalletResource($wallet),
        ]);
    }

    public function overview(Request $request)
    {
        $profile = $request->attributes->get('profile');

        $wallet = Wallet::query()
            ->where('user_id', $profile->id)
            ->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WALLET_NOT_FOUND',
                    'message' =>
                        'Wallet pengguna tidak ditemukan.',
                ],
            ], 404);
        }

        $transactions = $wallet
            ->transactions()
            ->latest()
            ->limit(20)
            ->get();

        $monthStart = Carbon::now(
            'Asia/Jakarta'
        )
            ->startOfMonth()
            ->utc();

        $successfulTransactions =
            $wallet
                ->transactions()
                ->where(
                    'status',
                    'completed'
                )
                ->where(
                    'created_at',
                    '>=',
                    $monthStart
                );

        $totalTopUp =
            (clone $successfulTransactions)
                ->whereIn(
                    'type',
                    [
                        'topup',
                        'top_up',
                    ]
                )
                ->where(
                    'direction',
                    'credit'
                )
                ->sum('amount');

        $totalOutflow =
            (clone $successfulTransactions)
                ->where(
                    'direction',
                    'debit'
                )
                ->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'wallet' =>
                    (new WalletResource(
                        $wallet
                    ))->resolve($request),

                'transactions' =>
                    WalletTransactionResource::collection(
                        $transactions
                    )->resolve($request),

                'monthly_summary' => [
                    'total_top_up' =>
                        (int) $totalTopUp,

                    'total_outflow' =>
                        (int) $totalOutflow,
                ],
            ],
        ]);
    }

    public function transactions(Request $request)
    {
        $profile = $request->attributes->get('profile');

        $wallet = Wallet::query()
            ->where('user_id', $profile->id)
            ->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WALLET_NOT_FOUND',
                    'message' => 'Wallet pengguna tidak ditemukan.',
                ],
            ], 404);
        }

        $transactions = $wallet
            ->transactions()
            ->latest()
            ->paginate(20);

        return WalletTransactionResource::collection(
            $transactions
        )->additional([
            'success' => true,
        ]);
    }
}