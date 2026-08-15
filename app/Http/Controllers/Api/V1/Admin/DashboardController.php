<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\EscrowTransaction;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\StudentProfile;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        $students = StudentProfile::query()->count();

        $merchants = Merchant::query()->count();

        $activeMerchants = Merchant::query()
            ->where('is_active', true)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Orders
        |--------------------------------------------------------------------------
        */

        $orderStatusCounts = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalOrders = Order::query()->count();

        $completedOrderValue = Order::query()
            ->where('status', 'completed')
            ->sum('total_amount');

        /*
        |--------------------------------------------------------------------------
        | Top Up
        |--------------------------------------------------------------------------
        */

        $successfulTopUps = PaymentTransaction::query()
            ->where('provider', 'midtrans')
            ->where('status', 'success')
            ->sum('gross_amount');

        /*
        |--------------------------------------------------------------------------
        | Escrow
        |--------------------------------------------------------------------------
        */

        $escrowHeld = EscrowTransaction::query()
            ->where('status', 'held')
            ->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | Withdrawal
        |--------------------------------------------------------------------------
        */

        $pendingWithdrawals = WithdrawalRequest::query()
            ->where('status', 'waiting')
            ->count();

        $pendingWithdrawalAmount = WithdrawalRequest::query()
            ->where('status', 'waiting')
            ->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | Recent Orders
        |--------------------------------------------------------------------------
        */

        $recentOrders = Order::query()
            ->with([
                'student:id,name',
                'merchant:id,name,type',
            ])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function (Order $order) {
                return [
                    'id' => $order->id,
                    'order_code' => $order->order_code,

                    'student' => [
                        'id' => $order->student?->id,
                        'name' => $order->student?->name,
                    ],

                    'merchant' => [
                        'id' => $order->merchant?->id,
                        'name' => $order->merchant?->name,
                        'type' => $order->merchant?->type,
                    ],

                    'status' => $order->status,

                    'total_amount' =>
                        (int) $order->total_amount,

                    'created_at' =>
                        $order->created_at?->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,

            'data' => [
                'users' => [
                    'students' => $students,
                    'merchants' => $merchants,
                    'active_merchants' => $activeMerchants,
                ],

                'orders' => [
                    'total' => $totalOrders,

                    'waiting' =>
                        (int) ($orderStatusCounts['waiting'] ?? 0),

                    'confirmed' =>
                        (int) ($orderStatusCounts['confirmed'] ?? 0),

                    'preparing' =>
                        (int) ($orderStatusCounts['preparing'] ?? 0),

                    'ready' =>
                        (int) ($orderStatusCounts['ready'] ?? 0),

                    'completed' =>
                        (int) ($orderStatusCounts['completed'] ?? 0),

                    'cancelled' =>
                        (int) ($orderStatusCounts['cancelled'] ?? 0),
                ],

                'finance' => [
                    'completed_order_value' =>
                        (int) $completedOrderValue,

                    'successful_topups' =>
                        (int) $successfulTopUps,

                    'escrow_held' =>
                        (int) $escrowHeld,

                    'pending_withdrawals' =>
                        $pendingWithdrawals,

                    'pending_withdrawal_amount' =>
                        (int) $pendingWithdrawalAmount,
                ],

                'recent_orders' => $recentOrders,
            ],
        ]);
    }
}