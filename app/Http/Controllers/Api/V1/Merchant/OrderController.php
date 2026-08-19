<?php

namespace App\Http\Controllers\Api\V1\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantOrderResource;
use App\Models\Merchant;
use App\Models\Order;
use App\Http\Requests\Api\V1\Merchant\UpdateOrderStatusRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class OrderController extends Controller
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

        $orders = Order::query()
            ->where('merchant_id', $merchant->id)
            ->with([
                'student',
                'pickupSlot',
                'items.modifiers',
                'pickup',
            ])
            ->withCount('items')
            ->latest()
            ->paginate(10);

        return MerchantOrderResource::collection($orders)
            ->additional([
                'success' => true,
            ]);
    }

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        string $order
    ) {
        $profile = $request->attributes->get('profile');
        $data = $request->validated();

        $merchantOrder = DB::transaction(function () use (
            $profile,
            $order,
            $data
        ) {
            $merchant = Merchant::query()
                ->where('owner_user_id', $profile->id)
                ->first();

            if (!$merchant) {
                return null;
            }

            $merchantOrder = Order::query()
                ->where('merchant_id', $merchant->id)
                ->whereKey($order)
                ->lockForUpdate()
                ->first();

            if (!$merchantOrder) {
                return null;
            }

            $allowedTransitions = [
                'waiting' => 'confirmed',
                'confirmed' => 'preparing',
                'preparing' => 'ready',
            ];

            $expectedStatus =
                $allowedTransitions[$merchantOrder->status] ?? null;

            if ($expectedStatus !== $data['status']) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_STATUS_TRANSITION',
                        'message' =>
                            "Status {$merchantOrder->status} tidak dapat diubah menjadi {$data['status']}.",
                    ],
                ], 409);
            }

            $merchantOrder->status = $data['status'];

            if ($data['status'] === 'confirmed') {
                $merchantOrder->confirmed_at = now();
            }

            if ($data['status'] === 'preparing') {
                $merchantOrder->preparing_at = now();
            }

            if ($data['status'] === 'ready') {
                $merchantOrder->ready_at = now();
            }

            $merchantOrder->save();

            return $merchantOrder;
        });

        if (!$merchantOrder) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ORDER_NOT_FOUND',
                    'message' => 'Pesanan tidak ditemukan.',
                ],
            ], 404);
        }

        if ($merchantOrder instanceof \Illuminate\Http\JsonResponse) {
            return $merchantOrder;
        }

        $merchantOrder->load([
            'student',
            'pickupSlot',
            'items.modifiers',
            'pickup',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status pesanan berhasil diperbarui.',
            'data' => new MerchantOrderResource($merchantOrder),
        ]);
    }

    public function show(Request $request, string $order)
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

        $merchantOrder = Order::query()
            ->where('merchant_id', $merchant->id)
            ->whereKey($order)
            ->with([
                'student',
                'pickupSlot',
                'items.modifiers',
                'pickup',
            ])
            ->first();

        if (!$merchantOrder) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ORDER_NOT_FOUND',
                    'message' => 'Pesanan tidak ditemukan.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new MerchantOrderResource($merchantOrder),
        ]);
    }
}