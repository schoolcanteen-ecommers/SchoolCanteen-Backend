<?php

namespace App\Http\Requests\Api\V1\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pickup_token' => [
                'nullable',
                'string',
                'max:255',
                'required_without:pickup_code',
            ],

            'pickup_code' => [
                'nullable',
                'string',
                'max:20',
                'required_without:pickup_token',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_token.required_without' =>
                'Pickup token atau pickup code wajib diisi.',

            'pickup_code.required_without' =>
                'Pickup token atau pickup code wajib diisi.',
        ];
    }
}