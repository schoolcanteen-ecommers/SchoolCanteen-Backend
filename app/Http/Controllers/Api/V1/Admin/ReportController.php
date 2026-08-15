<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\AdminReportRequest;
use App\Models\EscrowTransaction;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\WithdrawalRequest;

class ReportController extends Controller
{
    public function summary(AdminReportRequest $request)
    {
        $data = $request->validated();

        $dateFrom = $data['date_from'] ?? null;
        $dateTo = $data['date_to'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Orders
        |--------------------------------------------------------------------------
        */

        $orderQuery = Order::query();

        $this->applyPeriod(
            $orderQuery,
            $dateFrom,
            $dateTo
        );

        $orderStatusCounts = (clone $orderQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $placedOrderValue = (int) (
            clone $orderQuery
        )->sum('total_amount');

        $completedOrderValue = (int) (
            clone $orderQuery
        )
            ->where('status', 'completed')
            ->sum('total_amount');

        /*
        |--------------------------------------------------------------------------
        | Top Ups
        |--------------------------------------------------------------------------
        |
        | payment_transactions digunakan sebagai source untuk payment provider.
        | Tidak digabung dengan wallet_transactions agar top up tidak dihitung
        | dua kali.
        |
        */

        $paymentQuery =
            PaymentTransaction::query();

        $this->applyPeriod(
            $paymentQuery,
            $dateFrom,
            $dateTo
        );

        $paymentStats = (clone $paymentQuery)
            ->selectRaw(
                'status, COUNT(*) as total, COALESCE(SUM(gross_amount), 0) as amount'
            )
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        /*
        |--------------------------------------------------------------------------
        | Escrow
        |--------------------------------------------------------------------------
        */

        $escrowQuery =
            EscrowTransaction::query();

        $this->applyPeriod(
            $escrowQuery,
            $dateFrom,
            $dateTo
        );

        $escrowStats = (clone $escrowQuery)
            ->selectRaw(
                'status, COUNT(*) as total, COALESCE(SUM(amount), 0) as amount'
            )
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        /*
        |--------------------------------------------------------------------------
        | Withdrawals
        |--------------------------------------------------------------------------
        */

        $withdrawalQuery =
            WithdrawalRequest::query();

        $this->applyPeriod(
            $withdrawalQuery,
            $dateFrom,
            $dateTo
        );

        $withdrawalStats =
            (clone $withdrawalQuery)
                ->selectRaw(
                    'status, COUNT(*) as total, COALESCE(SUM(amount), 0) as amount'
                )
                ->groupBy('status')
                ->get()
                ->keyBy('status');

        /*
        |--------------------------------------------------------------------------
        | Merchant Performance
        |--------------------------------------------------------------------------
        */

        $merchantPerformance =
            Order::query()
                ->join(
                    'merchants',
                    'merchants.id',
                    '=',
                    'orders.merchant_id'
                )
                ->select([
                    'merchants.id',
                    'merchants.name',
                    'merchants.type',
                ])
                ->selectRaw(
                    'COUNT(orders.id) as orders_count'
                )
                ->selectRaw(
                    "SUM(
                        CASE
                            WHEN orders.status = 'completed'
                            THEN 1
                            ELSE 0
                        END
                    ) as completed_orders"
                )
                ->selectRaw(
                    "COALESCE(
                        SUM(
                            CASE
                                WHEN orders.status = 'completed'
                                THEN orders.total_amount
                                ELSE 0
                            END
                        ),
                        0
                    ) as completed_order_value"
                );

        if ($dateFrom) {
            $merchantPerformance->whereDate(
                'orders.created_at',
                '>=',
                $dateFrom
            );
        }

        if ($dateTo) {
            $merchantPerformance->whereDate(
                'orders.created_at',
                '<=',
                $dateTo
            );
        }

        $merchantPerformance =
            $merchantPerformance
                ->groupBy(
                    'merchants.id',
                    'merchants.name',
                    'merchants.type'
                )
                ->orderByDesc(
                    'completed_order_value'
                )
                ->get()
                ->map(function ($merchant) {
                    return [
                        'id' => $merchant->id,
                        'name' => $merchant->name,
                        'type' => $merchant->type,

                        'orders_count' =>
                            (int) $merchant->orders_count,

                        'completed_orders' =>
                            (int) $merchant->completed_orders,

                        'completed_order_value' =>
                            (int) $merchant
                                ->completed_order_value,
                    ];
                });

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'data' => [
                'period' => [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'basis' => 'created_at',
                ],

                'orders' => [
                    'total' =>
                        (int) $orderStatusCounts->sum(),

                    'waiting' =>
                        (int) (
                            $orderStatusCounts['waiting']
                            ?? 0
                        ),

                    'confirmed' =>
                        (int) (
                            $orderStatusCounts['confirmed']
                            ?? 0
                        ),

                    'preparing' =>
                        (int) (
                            $orderStatusCounts['preparing']
                            ?? 0
                        ),

                    'ready' =>
                        (int) (
                            $orderStatusCounts['ready']
                            ?? 0
                        ),

                    'completed' =>
                        (int) (
                            $orderStatusCounts['completed']
                            ?? 0
                        ),

                    'cancelled' =>
                        (int) (
                            $orderStatusCounts['cancelled']
                            ?? 0
                        ),

                    'placed_order_value' =>
                        $placedOrderValue,

                    'completed_order_value' =>
                        $completedOrderValue,
                ],

                'topups' => [
                    'success' =>
                        $this->stat(
                            $paymentStats,
                            'success'
                        ),

                    'pending' =>
                        $this->stat(
                            $paymentStats,
                            'pending'
                        ),

                    'failed' =>
                        $this->stat(
                            $paymentStats,
                            'failed'
                        ),

                    'expired' =>
                        $this->stat(
                            $paymentStats,
                            'expired'
                        ),
                ],

                'escrow' => [
                    'held' =>
                        $this->stat(
                            $escrowStats,
                            'held'
                        ),

                    'released' =>
                        $this->stat(
                            $escrowStats,
                            'released'
                        ),

                    'refunded' =>
                        $this->stat(
                            $escrowStats,
                            'refunded'
                        ),
                ],

                'withdrawals' => [
                    'waiting' =>
                        $this->stat(
                            $withdrawalStats,
                            'waiting'
                        ),

                    'approved' =>
                        $this->stat(
                            $withdrawalStats,
                            'approved'
                        ),

                    'processed' =>
                        $this->stat(
                            $withdrawalStats,
                            'processed'
                        ),

                    'completed' =>
                        $this->stat(
                            $withdrawalStats,
                            'completed'
                        ),

                    'rejected' =>
                        $this->stat(
                            $withdrawalStats,
                            'rejected'
                        ),
                ],

                'merchant_performance' =>
                    $merchantPerformance,
            ],
        ]);
    }

    private function applyPeriod(
        $query,
        ?string $dateFrom,
        ?string $dateTo
    ): void {
        if ($dateFrom) {
            $query->whereDate(
                'created_at',
                '>=',
                $dateFrom
            );
        }

        if ($dateTo) {
            $query->whereDate(
                'created_at',
                '<=',
                $dateTo
            );
        }
    }

    private function stat(
        $stats,
        string $status
    ): array {
        $item = $stats->get($status);

        return [
            'count' =>
                (int) ($item?->total ?? 0),

            'amount' =>
                (int) ($item?->amount ?? 0),
        ];
    }
}