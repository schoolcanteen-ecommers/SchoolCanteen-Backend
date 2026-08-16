<?php

namespace App\Http\Controllers\Api\V1\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Merchant\VerifyPickupRequest;
use App\Models\EscrowTransaction;
use App\Models\Merchant;
use App\Models\MerchantWallet;
use App\Models\MerchantWalletTransaction;
use App\Models\Order;
use App\Models\Pickup;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PickupController extends Controller
{
    public function verify(VerifyPickupRequest $request)
    {
        $profile = $request->attributes->get('profile');
        $data = $request->validated();

        $result = DB::transaction(function () use (
            $profile,
            $data
        ) {
            /*
            |--------------------------------------------------------------------------
            | Merchant
            |--------------------------------------------------------------------------
            */

            $merchant = Merchant::query()
                ->where('owner_user_id', $profile->id)
                ->lockForUpdate()
                ->first();

            if (!$merchant) {
                $this->fail(
                    'MERCHANT_NOT_FOUND',
                    'Merchant tidak ditemukan.',
                    404
                );
            }

            if (!$merchant->is_active) {
                $this->fail(
                    'MERCHANT_INACTIVE',
                    'Merchant sedang tidak aktif.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Pickup
            |--------------------------------------------------------------------------
            */

            $pickupQuery = Pickup::query()
                ->whereHas(
                    'order',
                    function ($query) use ($merchant) {
                        $query->where(
                            'merchant_id',
                            $merchant->id
                        );
                    }
                );

            if (!empty($data['pickup_token'])) {
                $pickupQuery->where(
                    'pickup_token',
                    $data['pickup_token']
                );
            } else {
                $pickupQuery->where(
                    'pickup_code',
                    $data['pickup_code']
                );
            }

            $pickup = $pickupQuery
                ->lockForUpdate()
                ->first();

            if (!$pickup) {
                $this->fail(
                    'PICKUP_NOT_FOUND',
                    'Data pickup tidak ditemukan.',
                    404
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Order Ownership
            |--------------------------------------------------------------------------
            */

            $order = Order::query()
                ->whereKey($pickup->order_id)
                ->where('merchant_id', $merchant->id)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                $this->fail(
                    'PICKUP_NOT_FOUND',
                    'Data pickup tidak ditemukan.',
                    404
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Pickup & Order Status
            |--------------------------------------------------------------------------
            */

            if ($pickup->status !== 'waiting') {
                $this->fail(
                    'PICKUP_ALREADY_VERIFIED',
                    'Pesanan sudah pernah diambil.'
                );
            }

            if ($order->status !== 'ready') {
                $this->fail(
                    'ORDER_NOT_READY',
                    'Pesanan belum siap untuk diambil.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Escrow
            |--------------------------------------------------------------------------
            */

            $escrow = EscrowTransaction::query()
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if (!$escrow) {
                $this->fail(
                    'ESCROW_NOT_FOUND',
                    'Escrow pesanan tidak ditemukan.',
                    404
                );
            }

            if ($escrow->status !== 'held') {
                $this->fail(
                    'ESCROW_NOT_HELD',
                    'Dana pesanan sudah tidak berada dalam escrow.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Merchant Wallet
            |--------------------------------------------------------------------------
            */

            $merchantWallet = MerchantWallet::query()
                ->where('merchant_id', $merchant->id)
                ->lockForUpdate()
                ->first();

            if (!$merchantWallet) {
                $this->fail(
                    'MERCHANT_WALLET_NOT_FOUND',
                    'Wallet merchant tidak ditemukan.',
                    404
                );
            }

            if (!$merchantWallet->is_active) {
                $this->fail(
                    'MERCHANT_WALLET_INACTIVE',
                    'Wallet merchant sedang tidak aktif.'
                );
            }

            if (
                $merchantWallet->pending_balance
                < $escrow->amount
            ) {
                $this->fail(
                    'INVALID_PENDING_BALANCE',
                    'Saldo pending merchant tidak mencukupi.'
                );
            }

            $now = now();

            /*
            |--------------------------------------------------------------------------
            | Verify Pickup
            |--------------------------------------------------------------------------
            */

            $pickup->status = 'verified';
            $pickup->verified_by = $profile->id;
            $pickup->verified_at = $now;
            $pickup->save();

            /*
            |--------------------------------------------------------------------------
            | Complete Order
            |--------------------------------------------------------------------------
            */

            $order->status = 'completed';
            $order->completed_at = $now;
            $order->save();

            /*
            |--------------------------------------------------------------------------
            | Release Escrow
            |--------------------------------------------------------------------------
            */

            $escrow->status = 'released';
            $escrow->released_at = $now;
            $escrow->save();

            /*
            |--------------------------------------------------------------------------
            | Move Merchant Balance
            |--------------------------------------------------------------------------
            */

            $merchantWallet->pending_balance -= $escrow->amount;
            $merchantWallet->available_balance += $escrow->amount;
            $merchantWallet->save();

            /*
            |--------------------------------------------------------------------------
            | Merchant Wallet Ledger
            |--------------------------------------------------------------------------
            |
            | Pending → Available adalah transfer internal.
            | Karena itu kita catat debit pending dan credit available
            | agar ledger tidak menghitung dana merchant dua kali.
            |
            */

            $pendingTransaction =
                new MerchantWalletTransaction();

            $pendingTransaction->id =
                Str::uuid()->toString();

            $pendingTransaction->merchant_wallet_id =
                $merchantWallet->id;

            $pendingTransaction->type =
                'pending_release';

            $pendingTransaction->direction =
                'debit';

            $pendingTransaction->amount =
                $escrow->amount;

            $pendingTransaction->status =
                'completed';

            $pendingTransaction->reference_type =
                'order';

            $pendingTransaction->reference_id =
                $order->id;

            $pendingTransaction->description =
                "Pelepasan saldo pending {$order->order_code}";

            $pendingTransaction->save();


            $availableTransaction =
                new MerchantWalletTransaction();

            $availableTransaction->id =
                Str::uuid()->toString();

            $availableTransaction->merchant_wallet_id =
                $merchantWallet->id;

            $availableTransaction->type =
                'available_release';

            $availableTransaction->direction =
                'credit';

            $availableTransaction->amount =
                $escrow->amount;

            $availableTransaction->status =
                'completed';

            $availableTransaction->reference_type =
                'order';

            $availableTransaction->reference_id =
                $order->id;

            $availableTransaction->description =
                "Dana tersedia pesanan {$order->order_code}";

            $availableTransaction->save();

            return [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'order_status' => $order->status,

                'pickup_status' => $pickup->status,
                'verified_at' =>
                    $pickup->verified_at?->toISOString(),

                'escrow' => [
                    'status' => $escrow->status,
                    'amount' => (int) $escrow->amount,
                    'released_at' =>
                        $escrow->released_at?->toISOString(),
                ],

                'merchant_wallet' => [
                    'pending_balance' =>
                        (int) $merchantWallet->pending_balance,

                    'available_balance' =>
                        (int) $merchantWallet->available_balance,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil diambil.',
            'data' => $result,
        ]);
    }

    private function fail(
        string $code,
        string $message,
        int $status = 409
    ): never {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error' => [
                    'code' => $code,
                    'message' => $message,
                ],
            ], $status)
        );
    }
}