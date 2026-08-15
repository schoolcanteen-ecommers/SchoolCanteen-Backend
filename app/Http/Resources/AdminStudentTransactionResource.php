<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminStudentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'ledger' => 'student_wallet',

            'student' => $this->whenLoaded(
                'wallet',
                function () {
                    return [
                        'id' => $this->wallet?->user?->id,
                        'name' => $this->wallet?->user?->name,
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

            'payment' => $this->whenLoaded(
                'paymentTransaction',
                function () {
                    if (!$this->paymentTransaction) {
                        return null;
                    }

                    return [
                        'id' =>
                            $this->paymentTransaction->id,

                        'provider' =>
                            $this->paymentTransaction->provider,

                        'provider_order_id' =>
                            $this->paymentTransaction
                                ->provider_order_id,

                        'provider_transaction_id' =>
                            $this->paymentTransaction
                                ->provider_transaction_id,

                        'payment_type' =>
                            $this->paymentTransaction
                                ->payment_type,

                        'status' =>
                            $this->paymentTransaction->status,

                        'gross_amount' =>
                            (int) $this->paymentTransaction
                                ->gross_amount,

                        'paid_at' =>
                            $this->paymentTransaction
                                ->paid_at?->toISOString(),

                        'expired_at' =>
                            $this->paymentTransaction
                                ->expired_at?->toISOString(),
                    ];
                }
            ),

            'created_at' =>
                $this->created_at?->toISOString(),
        ];
    }
}