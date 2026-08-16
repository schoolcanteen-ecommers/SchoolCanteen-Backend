<?php

namespace App\Http\Requests\Api\V1\Merchant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'integer',
                'min:1',
            ],

            'method' => [
                'required',
                'string',
                Rule::in([
                    'cash',
                    'bank',
                    'e_wallet',
                ]),
            ],

            'payment_account_id' => [
                'nullable',
                'uuid',
                'required_unless:method,cash',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' =>
                'Jumlah penarikan wajib diisi.',

            'amount.min' =>
                'Jumlah penarikan harus lebih dari 0.',

            'method.required' =>
                'Metode penarikan wajib dipilih.',

            'method.in' =>
                'Metode penarikan tidak valid.',

            'payment_account_id.required_unless' =>
                'Akun pembayaran wajib dipilih untuk penarikan digital.',
        ];
    }
}