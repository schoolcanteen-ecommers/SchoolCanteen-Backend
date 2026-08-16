<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();

        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;
        $transactionStatus =
            $payload['transaction_status'] ?? null;

        if (
            !$orderId ||
            !$statusCode ||
            !$grossAmount ||
            !$signatureKey ||
            !$transactionStatus
        ) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_NOTIFICATION',
                    'message' => 'Payload notifikasi tidak lengkap.',
                ],
            ], 400);
        }

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

        $expectedSignature = hash(
            'sha512',
            $orderId .
            $statusCode .
            $grossAmount .
            $serverKey
        );

        if (!hash_equals($expectedSignature, $signatureKey)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_SIGNATURE',
                    'message' => 'Signature Midtrans tidak valid.',
                ],
            ], 401);
        }

        DB::transaction(function () use (
            $payload,
            $orderId,
            $grossAmount,
            $transactionStatus
        ) {
            $paymentTransaction =
                PaymentTransaction::query()
                    ->where(
                        'provider_order_id',
                        $orderId
                    )
                    ->lockForUpdate()
                    ->first();

            if (!$paymentTransaction) {
                abort(
                    response()->json([
                        'success' => false,
                        'error' => [
                            'code' =>
                                'PAYMENT_TRANSACTION_NOT_FOUND',

                            'message' =>
                                'Transaksi pembayaran tidak ditemukan.',
                        ],
                    ], 404)
                );
            }

            $walletTransaction =
                WalletTransaction::query()
                    ->whereKey(
                        $paymentTransaction
                            ->wallet_transaction_id
                    )
                    ->lockForUpdate()
                    ->first();

            if (!$walletTransaction) {
                abort(
                    response()->json([
                        'success' => false,
                        'error' => [
                            'code' =>
                                'WALLET_TRANSACTION_NOT_FOUND',

                            'message' =>
                                'Transaksi wallet tidak ditemukan.',
                        ],
                    ], 404)
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Amount
            |--------------------------------------------------------------------------
            */

            $notificationAmount =
                (int) round((float) $grossAmount);

            if (
                $notificationAmount !==
                (int) $paymentTransaction->gross_amount
            ) {
                abort(
                    response()->json([
                        'success' => false,
                        'error' => [
                            'code' =>
                                'INVALID_PAYMENT_AMOUNT',

                            'message' =>
                                'Nominal pembayaran tidak sesuai.',
                        ],
                    ], 409)
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Store Provider Information
            |--------------------------------------------------------------------------
            */

            if (!empty($payload['transaction_id'])) {
                $paymentTransaction
                    ->provider_transaction_id =
                    $payload['transaction_id'];
            }

            if (!empty($payload['payment_type'])) {
                $paymentTransaction->payment_type =
                    $payload['payment_type'];
            }

            $paymentTransaction->provider_response =
                $payload;

            /*
            |--------------------------------------------------------------------------
            | Idempotency
            |--------------------------------------------------------------------------
            |
            | Jika saldo sudah pernah dikreditkan untuk transaksi ini,
            | notification berikutnya tidak boleh menambah saldo lagi.
            |
            */

            if (
                $paymentTransaction->status === 'success' &&
                $walletTransaction->status === 'completed'
            ) {
                $paymentTransaction->save();

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Successful Payment
            |--------------------------------------------------------------------------
            */

            $fraudStatus =
                $payload['fraud_status'] ?? null;

            $isSettlement =
                $transactionStatus === 'settlement';

            $isAcceptedCapture =
                $transactionStatus === 'capture' &&
                (
                    $fraudStatus === null ||
                    $fraudStatus === 'accept'
                );

            if ($isSettlement || $isAcceptedCapture) {
                $wallet = Wallet::query()
                    ->whereKey($walletTransaction->wallet_id)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) {
                    abort(
                        response()->json([
                            'success' => false,
                            'error' => [
                                'code' => 'WALLET_NOT_FOUND',
                                'message' =>
                                    'Wallet pengguna tidak ditemukan.',
                            ],
                        ], 404)
                    );
                }

                if (!$wallet->is_active) {
                    abort(
                        response()->json([
                            'success' => false,
                            'error' => [
                                'code' => 'WALLET_INACTIVE',
                                'message' =>
                                    'Wallet pengguna sedang tidak aktif.',
                            ],
                        ], 409)
                    );
                }

                $wallet->balance +=
                    $paymentTransaction->gross_amount;

                $wallet->save();

                $walletTransaction->status =
                    'completed';

                $walletTransaction->save();

                $paymentTransaction->status =
                    'success';

                $paymentTransaction->paid_at =
                    now();

                $paymentTransaction->save();

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Pending
            |--------------------------------------------------------------------------
            */

            if ($transactionStatus === 'pending') {
                $paymentTransaction->status =
                    'pending';

                $walletTransaction->status =
                    'pending';

                $walletTransaction->save();
                $paymentTransaction->save();

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Expired
            |--------------------------------------------------------------------------
            */

            if ($transactionStatus === 'expire') {
                $paymentTransaction->status =
                    'expired';

                $paymentTransaction->expired_at =
                    now();

                $walletTransaction->status =
                    'failed';

                $walletTransaction->save();
                $paymentTransaction->save();

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Failed
            |--------------------------------------------------------------------------
            */

            if (
                in_array(
                    $transactionStatus,
                    [
                        'deny',
                        'cancel',
                        'failure',
                    ],
                    true
                )
            ) {
                $paymentTransaction->status =
                    'failed';

                $walletTransaction->status =
                    'failed';

                $walletTransaction->save();
                $paymentTransaction->save();

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Unknown / Unsupported Status
            |--------------------------------------------------------------------------
            */

            Log::warning(
                'Unsupported Midtrans transaction status',
                [
                    'order_id' => $orderId,
                    'transaction_status' =>
                        $transactionStatus,
                ]
            );

            $paymentTransaction->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi Midtrans diproses.',
        ]);
    }
}