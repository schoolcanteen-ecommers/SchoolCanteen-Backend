<?php

namespace App\Http\Requests\Api\V1\Merchant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                Rule::in([
                    'bank',
                    'e_wallet',
                ]),
            ],

            'provider' => [
                'required',
                'string',
                'max:50',
            ],

            'account_number' => [
                'required',
                'string',
                'max:100',
            ],

            'account_name' => [
                'required',
                'string',
                'max:150',
            ],

            'is_default' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' =>
                'Tipe akun pembayaran wajib diisi.',

            'type.in' =>
                'Tipe akun pembayaran tidak valid.',

            'provider.required' =>
                'Provider wajib diisi.',

            'account_number.required' =>
                'Nomor rekening atau akun wajib diisi.',

            'account_name.required' =>
                'Nama pemilik akun wajib diisi.',
        ];
    }
}