<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantWalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'merchant' => $this->whenLoaded(
                'merchant',
                function () {
                    return [
                        'id' => $this->merchant->id,
                        'name' => $this->merchant->name,
                        'type' => $this->merchant->type,
                    ];
                }
            ),

            'pending_balance' => (int) $this->pending_balance,
            'available_balance' => (int) $this->available_balance,

            'total_balance' =>
                (int) $this->pending_balance
                + (int) $this->available_balance,

            'is_active' => (bool) $this->is_active,

            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}