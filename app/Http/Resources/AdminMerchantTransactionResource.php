<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMerchantTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'ledger' => 'merchant_wallet',

            'merchant' => $this->whenLoaded(
                'merchantWallet',
                function () {
                    return [
                        'id' =>
                            $this->merchantWallet?->merchant?->id,

                        'name' =>
                            $this->merchantWallet?->merchant?->name,

                        'type' =>
                            $this->merchantWallet?->merchant?->type,
                    ];
                }
            ),

            'type' => $this->type,
            'direction' => $this->direction,

            'amount' => (int) $this->amount,
            'status' => $this->status,

            'reference' => [
                'type' => $this->reference_type,
                'id' => $this->reference_id,
            ],

            'description' => $this->description,

            'created_at' =>
                $this->created_at?->toISOString(),
        ];
    }
}