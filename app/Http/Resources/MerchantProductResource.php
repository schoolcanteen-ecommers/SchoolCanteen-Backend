<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'category' => $this->whenLoaded(
                'category',
                function () {
                    if (!$this->category) {
                        return null;
                    }

                    return [
                        'id' => $this->category->id,
                        'name' => $this->category->name,
                        'slug' => $this->category->slug,
                    ];
                }
            ),

            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,

            'price' => (int) $this->price,
            'stock' => (int) $this->stock,

            'image_url' => $this->image_url,
            'is_active' => (bool) $this->is_active,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}