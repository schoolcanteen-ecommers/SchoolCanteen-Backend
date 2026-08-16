<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminWithdrawalResource;
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
        $query = WithdrawalRequest::query()
            ->with([
                'merchant:id,name,type',
                'paymentAccount',
                'approver:id,name',
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
                'merchant',
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
        | Merchant
        |--------------------------------------------------------------------------
        */

        if ($request->filled('merchant_id')) {
            $query->where(
                'merchant_id',
                $request->query('merchant_id')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->query('status')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Method
        |--------------------------------------------------------------------------
        */

        if ($request->filled('method')) {
            $query->where(
                'method',
                $request->query('method')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Date
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

        $withdrawals = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return AdminWithdrawalResource::collection(
            $withdrawals
        )->additional([
            'success' => true,
        ]);
    }

    public function show(string $withdrawal)
    {
        $withdrawalRequest =
            WithdrawalRequest::query()
                ->with([
                    'merchant:id,name,type',
                    'paymentAccount',
                    'approver:id,name',
                ])
                ->find($withdrawal);

        if (!$withdrawalRequest) {
            $this->fail(
                'WITHDRAWAL_NOT_FOUND',
                'Withdrawal tidak ditemukan.',
                404
            );
        }

        return response()->json([
            'success' => true,

            'data' => new AdminWithdrawalResource(
                $withdrawalRequest
            ),
        ]);
    }

    public function approve(
        Request $request,
        string $withdrawal
    ) {
        $profile = $request->attributes->get('profile');

        $withdrawalRequest = DB::transaction(
            function () use (
                $profile,
                $withdrawal
            ) {
                $withdrawalRequest =
                    WithdrawalRequest::query()
                        ->whereKey($withdrawal)
                        ->lockForUpdate()
                        ->first();

                if (!$withdrawalRequest) {
                    $this->fail(
                        'WITHDRAWAL_NOT_FOUND',
                        'Withdrawal tidak ditemukan.',
                        404
                    );
                }

                if ($withdrawalRequest->status !== 'waiting') {
                    $this->fail(
                        'INVALID_WITHDRAWAL_TRANSITION',
                        'Hanya withdrawal waiting yang dapat disetujui.'
                    );
                }

                $withdrawalRequest->status = 'approved';
                $withdrawalRequest->approved_by = $profile->id;
                $withdrawalRequest->approved_at = now();

                $withdrawalRequest->save();

                return $withdrawalRequest;
            }
        );

        return $this->successResponse(
            $withdrawalRequest,
            'Withdrawal berhasil disetujui.'
        );
    }

    public function process(string $withdrawal)
    {
        $withdrawalRequest = DB::transaction(
            function () use ($withdrawal) {
                $withdrawalRequest =
                    WithdrawalRequest::query()
                        ->whereKey($withdrawal)
                        ->lockForUpdate()
                        ->first();

                if (!$withdrawalRequest) {
                    $this->fail(
                        'WITHDRAWAL_NOT_FOUND',
                        'Withdrawal tidak ditemukan.',
                        404
                    );
                }

                if ($withdrawalRequest->status !== 'approved') {
                    $this->fail(
                        'INVALID_WITHDRAWAL_TRANSITION',
                        'Hanya withdrawal approved yang dapat diproses.'
                    );
                }

                $withdrawalRequest->status = 'processed';
                $withdrawalRequest->processed_at = now();

                $withdrawalRequest->save();

                return $withdrawalRequest;
            }
        );

        return $this->successResponse(
            $withdrawalRequest,
            'Withdrawal sedang diproses.'
        );
    }

    public function complete(string $withdrawal)
    {
        $withdrawalRequest = DB::transaction(
            function () use ($withdrawal) {
                $withdrawalRequest =
                    WithdrawalRequest::query()
                        ->whereKey($withdrawal)
                        ->lockForUpdate()
                        ->first();

                if (!$withdrawalRequest) {
                    $this->fail(
                        'WITHDRAWAL_NOT_FOUND',
                        'Withdrawal tidak ditemukan.',
                        404
                    );
                }

                if ($withdrawalRequest->status !== 'processed') {
                    $this->fail(
                        'INVALID_WITHDRAWAL_TRANSITION',
                        'Hanya withdrawal processed yang dapat diselesaikan.'
                    );
                }

                $withdrawalRequest->status = 'completed';
                $withdrawalRequest->completed_at = now();

                $withdrawalRequest->save();

                return $withdrawalRequest;
            }
        );

        return $this->successResponse(
            $withdrawalRequest,
            'Withdrawal berhasil diselesaikan.'
        );
    }

    public function reject(string $withdrawal)
    {
        $withdrawalRequest = DB::transaction(
            function () use ($withdrawal) {
                /*
                |--------------------------------------------------------------------------
                | Lock Withdrawal
                |--------------------------------------------------------------------------
                */

                $withdrawalRequest =
                    WithdrawalRequest::query()
                        ->whereKey($withdrawal)
                        ->lockForUpdate()
                        ->first();

                if (!$withdrawalRequest) {
                    $this->fail(
                        'WITHDRAWAL_NOT_FOUND',
                        'Withdrawal tidak ditemukan.',
                        404
                    );
                }

                if (!in_array(
                    $withdrawalRequest->status,
                    ['waiting', 'approved'],
                    true
                )) {
                    $this->fail(
                        'INVALID_WITHDRAWAL_TRANSITION',
                        'Withdrawal pada status ini tidak dapat ditolak.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Lock Merchant Wallet
                |--------------------------------------------------------------------------
                */

                $wallet = MerchantWallet::query()
                    ->where(
                        'merchant_id',
                        $withdrawalRequest->merchant_id
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) {
                    $this->fail(
                        'MERCHANT_WALLET_NOT_FOUND',
                        'Wallet merchant tidak ditemukan.',
                        404
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Verify Original Hold
                |--------------------------------------------------------------------------
                */

                $holdTransaction =
                    MerchantWalletTransaction::query()
                        ->where(
                            'merchant_wallet_id',
                            $wallet->id
                        )
                        ->where(
                            'type',
                            'withdrawal_hold'
                        )
                        ->where(
                            'reference_type',
                            'withdrawal'
                        )
                        ->where(
                            'reference_id',
                            $withdrawalRequest->id
                        )
                        ->first();

                if (!$holdTransaction) {
                    $this->fail(
                        'WITHDRAWAL_HOLD_NOT_FOUND',
                        'Ledger withdrawal hold tidak ditemukan.'
                    );
                }

                if (
                    (int) $holdTransaction->amount !==
                    (int) $withdrawalRequest->amount
                ) {
                    $this->fail(
                        'WITHDRAWAL_HOLD_MISMATCH',
                        'Nominal withdrawal hold tidak sesuai.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Idempotency Guard
                |--------------------------------------------------------------------------
                */

                $alreadyRefunded =
                    MerchantWalletTransaction::query()
                        ->where(
                            'merchant_wallet_id',
                            $wallet->id
                        )
                        ->where(
                            'type',
                            'withdrawal_refund'
                        )
                        ->where(
                            'reference_type',
                            'withdrawal'
                        )
                        ->where(
                            'reference_id',
                            $withdrawalRequest->id
                        )
                        ->exists();

                if ($alreadyRefunded) {
                    $this->fail(
                        'WITHDRAWAL_ALREADY_REFUNDED',
                        'Dana withdrawal sudah pernah dikembalikan.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Restore Available Balance
                |--------------------------------------------------------------------------
                */

                $wallet->available_balance +=
                    $withdrawalRequest->amount;

                $wallet->save();

                /*
                |--------------------------------------------------------------------------
                | Refund Ledger
                |--------------------------------------------------------------------------
                */

                $refundTransaction =
                    new MerchantWalletTransaction();

                $refundTransaction->id =
                    Str::uuid()->toString();

                $refundTransaction->merchant_wallet_id =
                    $wallet->id;

                $refundTransaction->type =
                    'withdrawal_refund';

                $refundTransaction->direction =
                    'credit';

                $refundTransaction->amount =
                    $withdrawalRequest->amount;

                $refundTransaction->status =
                    'completed';

                $refundTransaction->reference_type =
                    'withdrawal';

                $refundTransaction->reference_id =
                    $withdrawalRequest->id;

                $refundTransaction->description =
                    "Pengembalian dana withdrawal {$withdrawalRequest->id}";

                $refundTransaction->save();

                /*
                |--------------------------------------------------------------------------
                | Reject Withdrawal
                |--------------------------------------------------------------------------
                */

                $withdrawalRequest->status = 'rejected';
                $withdrawalRequest->rejected_at = now();

                $withdrawalRequest->save();

                return $withdrawalRequest;
            }
        );

        return $this->successResponse(
            $withdrawalRequest,
            'Withdrawal berhasil ditolak dan saldo dikembalikan.'
        );
    }

    private function successResponse(
        WithdrawalRequest $withdrawal,
        string $message
    ) {
        $withdrawal->load([
            'merchant:id,name,type',
            'paymentAccount',
            'approver:id,name',
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' =>
                new AdminWithdrawalResource($withdrawal),
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