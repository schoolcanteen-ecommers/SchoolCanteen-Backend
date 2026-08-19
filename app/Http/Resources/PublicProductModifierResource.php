<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProductModifierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' =>
                $this->id,

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
                                ]
                            )
                            ->values()
                            ->all()
                ),
        ];
    }
}
