<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'amount' => (int) $this->amount,
            'method' => $this->method,
            'status' => $this->status,
            'notes' => $this->notes,

            'payment_account' => $this->whenLoaded(
                'paymentAccount',
                function () {
                    if (!$this->paymentAccount) {
                        return null;
                    }

                    return [
                        'id' => $this->paymentAccount->id,
                        'type' => $this->paymentAccount->type,
                        'provider' => $this->paymentAccount->provider,
                        'account_number' =>
                            $this->paymentAccount->account_number,
                        'account_name' =>
                            $this->paymentAccount->account_name,
                    ];
                }
            ),

            'timeline' => [
                'approved_at' =>
                    $this->approved_at?->toISOString(),

                'processed_at' =>
                    $this->processed_at?->toISOString(),

                'completed_at' =>
                    $this->completed_at?->toISOString(),

                'rejected_at' =>
                    $this->rejected_at?->toISOString(),
            ],

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}