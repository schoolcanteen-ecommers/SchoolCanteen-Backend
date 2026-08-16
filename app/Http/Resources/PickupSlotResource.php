<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PickupSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $usedCapacity = (int) ($this->used_capacity ?? 0);

        return [
            'id' => $this->id,

            'start_at' => $this->start_at?->toISOString(),
            'end_at' => $this->end_at?->toISOString(),

            'capacity' => (int) $this->capacity,
            'used_capacity' => $usedCapacity,
            'remaining_capacity' => max(
                0,
                (int) $this->capacity - $usedCapacity
            ),
        ];
    }
}