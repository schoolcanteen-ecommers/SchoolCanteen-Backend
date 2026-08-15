<?php

namespace App\Http\Controllers\Api\V1\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantWalletResource;
use App\Http\Resources\MerchantWalletTransactionResource;
use App\Models\Merchant;
use App\Models\MerchantWallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request)
    {
        $profile = $request->attributes->get('profile');

        $merchant = Merchant::query()
            ->where('owner_user_id', $profile->id)
            ->first();

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MERCHANT_NOT_FOUND',
                    'message' => 'Merchant tidak ditemukan.',
                ],
            ], 404);
        }

        $wallet = MerchantWallet::query()
            ->where('merchant_id', $merchant->id)
            ->with('merchant')
            ->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MERCHANT_WALLET_NOT_FOUND',
                    'message' => 'Wallet merchant tidak ditemukan.',
                ],
            ], 404);
        }

        return (new MerchantWalletResource($wallet))
            ->additional([
                'success' => true,
            ]);
    }

    public function transactions(Request $request)
    {
        $profile = $request->attributes->get('profile');

        $merchant = Merchant::query()
            ->where('owner_user_id', $profile->id)
            ->first();

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MERCHANT_NOT_FOUND',
                    'message' => 'Merchant tidak ditemukan.',
                ],
            ], 404);
        }

        $wallet = MerchantWallet::query()
            ->where('merchant_id', $merchant->id)
            ->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MERCHANT_WALLET_NOT_FOUND',
                    'message' => 'Wallet merchant tidak ditemukan.',
                ],
            ], 404);
        }

        $transactions = $wallet
            ->transactions()
            ->latest()
            ->paginate(20);

        return MerchantWalletTransactionResource::collection(
            $transactions
        )->additional([
            'success' => true,
        ]);
    }
}