<?php

namespace App\Http\Controllers\Api\V1\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Merchant\StorePaymentAccountRequest;
use App\Http\Resources\MerchantPaymentAccountResource;
use App\Models\Merchant;
use App\Models\MerchantPaymentAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentAccountController extends Controller
{
    public function index(Request $request)
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

        $accounts = MerchantPaymentAccount::query()
            ->where('merchant_id', $merchant->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('provider')
            ->get();

        return MerchantPaymentAccountResource::collection(
            $accounts
        )->additional([
            'success' => true,
        ]);
    }

    public function store(StorePaymentAccountRequest $request)
    {
        $profile = $request->attributes->get('profile');
        $data = $request->validated();

        $account = DB::transaction(function () use (
            $profile,
            $data
        ) {
            $merchant = Merchant::query()
                ->where('owner_user_id', $profile->id)
                ->lockForUpdate()
                ->first();

            if (!$merchant) {
                return null;
            }

            $makeDefault = (bool) (
                $data['is_default'] ?? false
            );

            $hasAccount = MerchantPaymentAccount::query()
                ->where('merchant_id', $merchant->id)
                ->where('is_active', true)
                ->exists();

            if (!$hasAccount) {
                $makeDefault = true;
            }

            if ($makeDefault) {
                MerchantPaymentAccount::query()
                    ->where('merchant_id', $merchant->id)
                    ->update([
                        'is_default' => false,
                    ]);
            }

            $account = new MerchantPaymentAccount();

            $account->id = Str::uuid()->toString();
            $account->merchant_id = $merchant->id;

            $account->type = $data['type'];
            $account->provider = $data['provider'];
            $account->account_number =
                $data['account_number'];

            $account->account_name =
                $data['account_name'];

            $account->is_default = $makeDefault;
            $account->is_active = true;

            $account->save();

            return $account;
        });

        if (!$account) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MERCHANT_NOT_FOUND',
                    'message' => 'Merchant tidak ditemukan.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' =>
                'Akun pembayaran berhasil ditambahkan.',
            'data' =>
                new MerchantPaymentAccountResource($account),
        ], 201);
    }
}