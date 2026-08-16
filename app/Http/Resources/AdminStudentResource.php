<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminStudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,

            'student_profile' => $this->whenLoaded(
                'studentProfile',
                function () {
                    if (!$this->studentProfile) {
                        return null;
                    }

                    return [
                        'nis' => $this->studentProfile->nis,
                        'class' => $this->studentProfile->class,
                        'major' => $this->studentProfile->major,
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
                        'balance' =>
                            (int) $this->wallet->balance,

                        'is_active' =>
                            (bool) $this->wallet->is_active,

                        'updated_at' =>
                            $this->wallet
                                ->updated_at?->toISOString(),
                    ];
                }
            ),

            'orders_count' =>
                $this->whenCounted('orders'),

            'created_at' =>
                $this->created_at?->toISOString(),

            'updated_at' =>
                $this->updated_at?->toISOString(),
        ];
    }
}