<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Http\Resources\WalletTransactionResource;
use App\Models\Wallet;
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