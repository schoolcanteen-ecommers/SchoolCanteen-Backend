<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'status' => $this->status,
            'total_amount' => (int) $this->total_amount,
            'notes' => $this->notes,

            'merchant' => $this->whenLoaded(
                'merchant',
                function () {
                    return [
                        'id' => $this->merchant->id,
                        'name' => $this->merchant->name,
                        'type' => $this->merchant->type,
                        'logo_url' => $this->merchant->logo_url,
                    ];
                }
            ),

            'pickup_slot' => $this->whenLoaded(
                'pickupSlot',
                function () {
                    if (!$this->pickupSlot) {
                        return null;
                    }

                    return [
                        'id' => $this->pickupSlot->id,
                        'start_at' => $this->pickupSlot->start_at?->toISOString(),
                        'end_at' => $this->pickupSlot->end_at?->toISOString(),
                    ];
                }
            ),

            'items' => $this->whenLoaded(
                'items',
                function () {
                    return $this->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'product_name' => $item->product_name,
                            'product_image_url' =>
                                $item->product_image_url,
                            'unit_price' => (int) $item->unit_price,
                            'quantity' => (int) $item->quantity,
                            'subtotal' => (int) $item->subtotal,
                        ];
                    });
                }
            ),

            'pickup' => $this->whenLoaded(
                'pickup',
                function () {
                    if (!$this->pickup) {
                        return null;
                    }

                    return [
                        'token' => $this->pickup->pickup_token,
                        'code' => $this->pickup->pickup_code,
                        'status' => $this->pickup->status,
                        'verified_at' => $this->pickup->verified_at?->toISOString(),
                    ];
                }
            ),

            'escrow' => $this->whenLoaded(
                'escrow',
                function () {
                    if (!$this->escrow) {
                        return null;
                    }

                    return [
                        'amount' => (int) $this->escrow->amount,
                        'status' => $this->escrow->status,
                        'held_at' => $this->escrow->held_at?->toISOString(),
                        'released_at' => $this->escrow->released_at?->toISOString(),
                        'refunded_at' => $this->escrow->refunded_at?->toISOString(),
                    ];
                }
            ),

            'timeline' => [
                'confirmed_at' => $this->confirmed_at?->toISOString(),
                'preparing_at' => $this->preparing_at?->toISOString(),
                'ready_at' => $this->ready_at?->toISOString(),
                'completed_at' => $this->completed_at?->toISOString(),
                'cancelled_at' => $this->cancelled_at?->toISOString(),
            ],

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}