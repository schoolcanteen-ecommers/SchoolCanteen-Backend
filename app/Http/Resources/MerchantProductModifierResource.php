<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantProductModifierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' =>
                $this->id,

            'product_id' =>
                $this->product_id,

            'name' =>
                $this->name,

            'selection_type' =>
                $this->selection_type,

            'is_required' =>
                (bool) $this->is_required,

            'min_select' =>
                (int) $this->min_select,

            'max_select' =>
                (int) $this->max_select,

            'sort_order' =>
                (int) $this->sort_order,

            'is_active' =>
                (bool) $this->is_active,

            'options' =>
                $this->whenLoaded(
                    'options',
                    fn () =>
                        $this->options
                            ->map(
                                fn ($option) => [
                                    'id' =>
                                        $option->id,

                                    'name' =>
                                        $option->name,

                                    'price_delta' =>
                                        (int)
                                        $option->price_delta,

                                    'sort_order' =>
                                        (int)
                                        $option->sort_order,

                                    'is_active' =>
                                        (bool)
                                        $option->is_active,
                                ]
                            )
                            ->values()
                            ->all()
                ),

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),
        ];
    }
}
