<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Student\StoreTopUpRequest;
use App\Models\PaymentTransaction;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;
use Throwable;

class TopUpController extends Controller
{
    public function store(StoreTopUpRequest $request)
    {
        $profile = $request->attributes->get('profile');
        $data = $request->validated();

        $serverKey = config('services.midtrans.server_key');

        if (!$serverKey) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MIDTRANS_NOT_CONFIGURED',
                    'message' => 'Konfigurasi Midtrans belum tersedia.',
                ],
            ], 500);
        }

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

        if (!$wallet->is_active) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WALLET_INACTIVE',
                    'message' => 'Wallet pengguna sedang tidak aktif.',
                ],
            ], 409);
        }

        $providerOrderId =
            'TOPUP-' .
            now()->format('YmdHis') .
            '-' .
            Str::upper(Str::random(6));

        [$walletTransaction, $paymentTransaction] =
            DB::transaction(function () use (
                $wallet,
                $data,
                $providerOrderId
            ) {
                $walletTransaction = new WalletTransaction();

                $walletTransaction->id =
                    Str::uuid()->toString();

                $walletTransaction->wallet_id =
                    $wallet->id;

                $walletTransaction->type = 'topup';
                $walletTransaction->direction = 'credit';
                $walletTransaction->amount = $data['amount'];
                $walletTransaction->status = 'pending';

                $walletTransaction->description =
                    'Top up saldo melalui Midtrans';

                $walletTransaction->save();

                $paymentTransaction =
                    new PaymentTransaction();

                $paymentTransaction->id =
                    Str::uuid()->toString();

                $paymentTransaction->wallet_transaction_id =
                    $walletTransaction->id;

                $paymentTransaction->provider = 'midtrans';

                $paymentTransaction->provider_order_id =
                    $providerOrderId;

                $paymentTransaction->status = 'pending';

                $paymentTransaction->gross_amount =
                    $data['amount'];

                $paymentTransaction->save();

                $walletTransaction->reference_type =
                    'topup';

                $walletTransaction->reference_id =
                    $paymentTransaction->id;

                $walletTransaction->save();

                return [
                    $walletTransaction,
                    $paymentTransaction,
                ];
            });

        try {
            Config::$serverKey = $serverKey;

            Config::$isProduction = (bool) config(
                'services.midtrans.is_production',
                false
            );

            Config::$isSanitized = (bool) config(
                'services.midtrans.is_sanitized',
                true
            );

            Config::$is3ds = (bool) config(
                'services.midtrans.is_3ds',
                true
            );

            $params = [
                'transaction_details' => [
                    'order_id' =>
                        $paymentTransaction->provider_order_id,

                    'gross_amount' =>
                        $paymentTransaction->gross_amount,
                ],
            ];

            $snapTransaction =
                Snap::createTransaction($params);

            $paymentTransaction->provider_response = [
                'token' => $snapTransaction->token ?? null,
                'redirect_url' =>
                    $snapTransaction->redirect_url ?? null,
            ];

            $paymentTransaction->save();
        } catch (Throwable $exception) {
            DB::transaction(function () use (
                $walletTransaction,
                $paymentTransaction
            ) {
                $walletTransaction->status = 'failed';
                $walletTransaction->save();

                $paymentTransaction->status = 'failed';
                $paymentTransaction->save();
            });

            report($exception);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MIDTRANS_TRANSACTION_FAILED',
                    'message' =>
                        'Gagal membuat transaksi pembayaran Midtrans.',
                ],
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaksi top up berhasil dibuat.',
            'data' => [
                'id' => $paymentTransaction->id,

                'provider_order_id' =>
                    $paymentTransaction->provider_order_id,

                'amount' =>
                    (int) $paymentTransaction->gross_amount,

                'status' =>
                    $paymentTransaction->status,

                'snap_token' =>
                    $paymentTransaction
                        ->provider_response['token'] ?? null,

                'redirect_url' =>
                    $paymentTransaction
                        ->provider_response['redirect_url'] ?? null,
            ],
        ], 201);
    }
}