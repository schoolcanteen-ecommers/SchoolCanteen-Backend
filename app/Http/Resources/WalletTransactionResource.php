<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'type' => $this->type,
            'direction' => $this->direction,

            'amount' => (int) $this->amount,

            'status' => $this->status,

            'description' => $this->description,

            'reference' => [
                'type' => $this->reference_type,
                'id' => $this->reference_id,
            ],

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}