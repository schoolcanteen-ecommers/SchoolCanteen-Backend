<?php

namespace App\Http\Requests\Api\V1\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductModifierOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'price_delta' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
            ],

            'sort_order' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],

            'is_active' => [
                'sometimes',
                'required',
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('is_active')) {
            return;
        }

        $value =
            $this->input('is_active');

        if (is_string($value)) {
            $value = filter_var(
                $value,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );
        }

        $this->merge([
            'is_active' => $value,
        ]);
    }
}
