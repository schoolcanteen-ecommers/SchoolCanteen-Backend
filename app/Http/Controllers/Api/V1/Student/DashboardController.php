<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Models\Order;
use App\Models\Wallet;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
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

        $baseOrderQuery = Order::query()
            ->where('user_id', $profile->id);

        $activeOrders = (clone $baseOrderQuery)
            ->whereIn('status', [
                'waiting',
                'confirmed',
                'preparing',
                'ready',
            ])
            ->count();

        $completedOrders = (clone $baseOrderQuery)
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'active_orders' => $activeOrders,
                'completed_orders' => $completedOrders,
                'wallet' => new WalletResource($wallet),
            ],
        ]);
    }
}
