<?php

namespace App\Http\Controllers\Api\V1\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Merchant\StoreWithdrawalRequest;
use App\Http\Resources\WithdrawalRequestResource;
use App\Models\Merchant;
use App\Models\MerchantPaymentAccount;
use App\Models\MerchantWallet;
use App\Models\MerchantWalletTransaction;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WithdrawalController extends Controller
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

        $withdrawals = WithdrawalRequest::query()
            ->where('merchant_id', $merchant->id)
            ->with('paymentAccount')
            ->latest()
            ->paginate(20);

        return WithdrawalRequestResource::collection(
            $withdrawals
        )->additional([
            'success' => true,
        ]);
    }

    public function store(StoreWithdrawalRequest $request)
    {
        $profile = $request->attributes->get('profile');
        $data = $request->validated();

        $withdrawal = DB::transaction(function () use (
            $profile,
            $data
        ) {
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

            $wallet = MerchantWallet::query()
                ->where('merchant_id', $merchant->id)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                $this->fail(
                    'MERCHANT_WALLET_NOT_FOUND',
                    'Wallet merchant tidak ditemukan.',
                    404
                );
            }

            if (!$wallet->is_active) {
                $this->fail(
                    'MERCHANT_WALLET_INACTIVE',
                    'Wallet merchant sedang tidak aktif.'
                );
            }

            if ($wallet->available_balance < $data['amount']) {
                $this->fail(
                    'INSUFFICIENT_AVAILABLE_BALANCE',
                    'Saldo tersedia tidak mencukupi.'
                );
            }

            $paymentAccount = null;

            if ($data['method'] !== 'cash') {
                $paymentAccount = MerchantPaymentAccount::query()
                    ->whereKey($data['payment_account_id'])
                    ->where('merchant_id', $merchant->id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (!$paymentAccount) {
                    $this->fail(
                        'PAYMENT_ACCOUNT_NOT_FOUND',
                        'Akun pembayaran tidak ditemukan.',
                        404
                    );
                }

                if ($paymentAccount->type !== $data['method']) {
                    $this->fail(
                        'INVALID_PAYMENT_ACCOUNT',
                        'Tipe akun pembayaran tidak sesuai dengan metode penarikan.'
                    );
                }
            }

            $withdrawal = new WithdrawalRequest();

            $withdrawal->id = Str::uuid()->toString();
            $withdrawal->merchant_id = $merchant->id;

            $withdrawal->payment_account_id =
                $paymentAccount?->id;

            $withdrawal->amount = $data['amount'];
            $withdrawal->method = $data['method'];
            $withdrawal->status = 'waiting';
            $withdrawal->notes = $data['notes'] ?? null;

            $withdrawal->save();

            /*
            |--------------------------------------------------------------------------
            | Reserve Available Balance
            |--------------------------------------------------------------------------
            */

            $wallet->available_balance -= $data['amount'];
            $wallet->save();

            /*
            |--------------------------------------------------------------------------
            | Merchant Wallet Ledger
            |--------------------------------------------------------------------------
            */

            $transaction = new MerchantWalletTransaction();

            $transaction->id = Str::uuid()->toString();
            $transaction->merchant_wallet_id = $wallet->id;

            $transaction->type = 'withdrawal_hold';
            $transaction->direction = 'debit';
            $transaction->amount = $data['amount'];
            $transaction->status = 'completed';

            $transaction->reference_type = 'withdrawal';
            $transaction->reference_id = $withdrawal->id;

            $transaction->description =
                "Pengajuan penarikan dana {$withdrawal->id}";

            $transaction->save();

            return $withdrawal;
        });

        $withdrawal->load('paymentAccount');

        return response()->json([
            'success' => true,
            'message' =>
                'Pengajuan penarikan berhasil dibuat.',
            'data' =>
                new WithdrawalRequestResource($withdrawal),
        ], 201);
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