<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ResolveProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_ids' => [
                'required',
                'array',
                'min:1',
                'max:50',
            ],

            'product_ids.*' => [
                'required',
                'string',
                'max:64',
                'distinct',
            ],
        ];
    }
}
