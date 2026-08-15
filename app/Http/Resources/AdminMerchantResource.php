<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMerchantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,
            'type' => $this->type,

            'description' => $this->description,
            'logo_url' => $this->logo_url,

            'is_active' => (bool) $this->is_active,
            'is_open' => (bool) $this->is_open,

            'owner' => $this->whenLoaded(
                'owner',
                function () {
                    return [
                        'id' => $this->owner?->id,
                        'name' => $this->owner?->name,
                        'phone' => $this->owner?->phone,
                    ];
                }
            ),

            'wallet' => $this->whenLoaded(
                'wallet',
                function () {
                    if (!$this->wallet) {
                        return null;
                    }

                    return [
                        'pending_balance' =>
                            (int) $this->wallet->pending_balance,

                        'available_balance' =>
                            (int) $this->wallet->available_balance,

                        'total_balance' =>
                            (int) (
                                $this->wallet->pending_balance +
                                $this->wallet->available_balance
                            ),

                        'is_active' =>
                            (bool) $this->wallet->is_active,
                    ];
                }
            ),

            'payment_accounts' => $this->whenLoaded(
                'paymentAccounts',
                function () {
                    return $this->paymentAccounts
                        ->map(function ($account) {
                            return [
                                'id' => $account->id,
                                'type' => $account->type,
                                'provider' => $account->provider,

                                'account_number' =>
                                    $account->account_number,

                                'account_name' =>
                                    $account->account_name,

                                'is_default' =>
                                    (bool) $account->is_default,

                                'is_active' =>
                                    (bool) $account->is_active,
                            ];
                        });
                }
            ),

            'orders_count' =>
                $this->whenCounted('orders'),

            'products_count' =>
                $this->whenCounted('products'),

            'created_at' =>
                $this->created_at?->toISOString(),

            'updated_at' =>
                $this->updated_at?->toISOString(),
        ];
    }
}