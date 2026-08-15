<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'balance' => (int) $this->balance,
            'is_active' => (bool) $this->is_active,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}