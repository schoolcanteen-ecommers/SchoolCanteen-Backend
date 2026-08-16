<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PickupSlotResource;
use App\Models\Merchant;
use App\Models\PickupSlot;

class PickupSlotController extends Controller
{
    public function index(Merchant $merchant)
    {
        if (!$merchant->is_active) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MERCHANT_NOT_FOUND',
                    'message' => 'Merchant tidak ditemukan.',
                ],
            ], 404);
        }

        $pickupSlots = PickupSlot::query()
            ->where('merchant_id', $merchant->id)
            ->where('is_active', true)
            ->where('end_at', '>', now())
            ->withCount([
                'orders as used_capacity' => function ($query) {
                    $query->where('status', '!=', 'cancelled');
                },
            ])
            ->orderBy('start_at')
            ->get()
            ->filter(function ($slot) {
                return $slot->used_capacity < $slot->capacity;
            })
            ->values();

        return PickupSlotResource::collection($pickupSlots)
            ->additional([
                'success' => true,
            ]);
    }
}