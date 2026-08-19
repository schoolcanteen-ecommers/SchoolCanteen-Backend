<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' =>
                $this->id,

            'product_id' =>
                $this->product_id,

            /*
             * Snapshot product saat checkout.
             */
            'product_name' =>
                $this->product_name,

            'product_image_url' =>
                $this->product_image_url,

            /*
             * Final unit price:
             *
             * base product price
             * + selected modifier deltas.
             */
            'unit_price' =>
                (int) $this->unit_price,

            'quantity' =>
                (int) $this->quantity,

            'subtotal' =>
                (int) $this->subtotal,

            /*
             * Catatan spesifik order line.
             */
            'notes' =>
                $this->notes,

            /*
             * Snapshot modifier, bukan live
             * product modifier.
             */
            'modifiers' =>
                $this->whenLoaded(
                    'modifiers',
                    fn () =>
                        $this->modifiers
                            ->map(
                                fn ($modifier) => [
                                    'id' =>
                                        $modifier->id,

                                    'modifier_group_id' =>
                                        $modifier
                                            ->modifier_group_id,

                                    'modifier_option_id' =>
                                        $modifier
                                            ->modifier_option_id,

                                    'group_name' =>
                                        $modifier->group_name,

                                    'option_name' =>
                                        $modifier->option_name,

                                    'price_delta' =>
                                        (int)
                                        $modifier->price_delta,
                                ]
                            )
                            ->values()
                            ->all()
                ),
        ];
    }
}
