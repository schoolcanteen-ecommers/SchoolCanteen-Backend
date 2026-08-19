<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantProductModifierOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' =>
                $this->id,

            'modifier_group_id' =>
                $this->modifier_group_id,

            'name' =>
                $this->name,

            'price_delta' =>
                (int) $this->price_delta,

            'sort_order' =>
                (int) $this->sort_order,

            'is_active' =>
                (bool) $this->is_active,

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),
        ];
    }
}
