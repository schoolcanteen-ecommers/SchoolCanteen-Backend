<?php

namespace App\Http\Requests\Api\V1\Merchant;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'category_id' => [
                'nullable',
                'uuid',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'price' => [
                'required',
                'integer',
                'min:1',
            ],

            'stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $value = $this->input('is_active');

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

}