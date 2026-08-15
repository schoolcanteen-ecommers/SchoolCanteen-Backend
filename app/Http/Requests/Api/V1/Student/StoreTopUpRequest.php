<?php

namespace App\Http\Requests\Api\V1\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreTopUpRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Nominal top up wajib diisi.',
            'amount.integer' => 'Nominal top up harus berupa angka.',
            'amount.min' => 'Nominal top up harus lebih dari 0.',
        ];
    }
}