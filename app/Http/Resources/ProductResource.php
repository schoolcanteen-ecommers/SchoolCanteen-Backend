<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' =>
                $this->id,

            'name' =>
                $this->name,

            'slug' =>
                $this->slug,

            'description' =>
                $this->description,

            'price' =>
                (int) $this->price,

            'stock' =>
                (int) $this->stock,

            'image_url' =>
                $this->image_url,

            'is_active' =>
                (bool) $this->is_active,

            /*
             * Lightweight modifier flags.
             *
             * Public list/resolve memakai
             * withExists().
             *
             * Product detail dapat menghitung
             * dari relation yang sudah di-load.
             */
            'has_modifiers' =>
                $this->hasActiveModifiers(),

            'requires_customization' =>
                $this->requiresCustomization(),

            'merchant' =>
                $this->whenLoaded(
                    'merchant',
                    function () {
                        return [
                            'id' =>
                                $this->merchant->id,

                            'name' =>
                                $this->merchant->name,

                            'type' =>
                                $this->merchant->type,

                            'is_open' =>
                                (bool)
                                $this->merchant->is_open,
                        ];
                    }
                ),

            'category' =>
                $this->whenLoaded(
                    'category',
                    function () {
                        if (!$this->category) {
                            return null;
                        }

                        return [
                            'id' =>
                                $this->category->id,

                            'name' =>
                                $this->category->name,

                            'slug' =>
                                $this->category->slug,
                        ];
                    }
                ),

            /*
             * Hanya muncul pada Product Detail,
             * karena list/catalog tidak load
             * modifierGroups.
             */
            'modifier_groups' =>
                PublicProductModifierResource::collection(
                    $this->whenLoaded(
                        'modifierGroups'
                    )
                ),

            'created_at' =>
                $this->created_at
                    ?->toISOString(),
        ];
    }

    private function hasActiveModifiers(): bool
    {
        if (
            array_key_exists(
                'has_modifiers',
                $this->resource->getAttributes()
            )
        ) {
            return (bool)
                $this->resource
                    ->getAttribute(
                        'has_modifiers'
                    );
        }

        if (
            $this->resource
                ->relationLoaded(
                    'modifierGroups'
                )
        ) {
            return $this
                ->modifierGroups
                ->isNotEmpty();
        }

        return false;
    }

    private function requiresCustomization(): bool
    {
        if (
            array_key_exists(
                'requires_customization',
                $this->resource->getAttributes()
            )
        ) {
            return (bool)
                $this->resource
                    ->getAttribute(
                        'requires_customization'
                    );
        }

        if (
            $this->resource
                ->relationLoaded(
                    'modifierGroups'
                )
        ) {
            return $this
                ->modifierGroups
                ->contains(
                    fn ($group) =>
                        (bool)
                        $group->is_required
                );
        }

        return false;
    }
}
