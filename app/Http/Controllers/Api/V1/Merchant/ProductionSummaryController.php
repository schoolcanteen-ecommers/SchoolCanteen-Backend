<?php

namespace App\Http\Controllers\Api\V1\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class ProductionSummaryController extends Controller
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

        $items = OrderItem::query()
            ->selectRaw('
                product_id,
                product_name,
                SUM(quantity) as total_quantity,
                COUNT(DISTINCT order_id) as order_count
            ')
            ->whereHas('order', function ($query) use ($merchant) {
                $query
                    ->where('merchant_id', $merchant->id)
                    ->whereIn('status', [
                        'confirmed',
                        'preparing',
                    ]);
            })
            ->groupBy([
                'product_id',
                'product_name',
            ])
            ->orderBy('product_name')
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'total_quantity' => (int) $item->total_quantity,
                    'order_count' => (int) $item->order_count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'statuses' => [
                    'confirmed',
                    'preparing',
                ],
                'products' => $items,
            ],
        ]);
    }
}