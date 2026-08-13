<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,

            'price' => (int) $this->price,
            'stock' => (int) $this->stock,

            'image_url' => $this->image_url,

            'is_active' => (bool) $this->is_active,

            'merchant' => $this->whenLoaded('merchant', function () {
                return [
                    'id' => $this->merchant->id,
                    'name' => $this->merchant->name,
                    'type' => $this->merchant->type,
                    'is_open' => (bool) $this->merchant->is_open,
                ];
            }),

            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}