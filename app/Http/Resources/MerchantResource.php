<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner_user_id' => $this->owner_user_id,

            'name' => $this->name,
            'type' => $this->type,

            'description' => $this->description,
            'logo_url' => $this->logo_url,

            'is_active' => (bool) $this->is_active,
            'is_open' => (bool) $this->is_open,

            'products_count' => $this->whenCounted('products'),

            'categories' => $this->whenLoaded(
                'categories',
                function () {
                    return $this->categories->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                        ];
                    });
                }
            ),

            'products' => ProductResource::collection(
                $this->whenLoaded('products')
            ),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}