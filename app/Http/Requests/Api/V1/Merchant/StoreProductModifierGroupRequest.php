<?php

namespace App\Http\Requests\Api\V1\Merchant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductModifierGroupRequest extends FormRequest
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
                'max:100',
            ],

            'selection_type' => [
                'required',
                Rule::in([
                    'single',
                    'multiple',
                ]),
            ],

            'is_required' => [
                'required',
                'boolean',
            ],

            'min_select' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],

            'max_select' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
                'max:65535',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (
            [
                'is_required',
                'is_active',
            ] as $field
        ) {
            if (!$this->has($field)) {
                continue;
            }

            $value =
                $this->input($field);

            if (is_string($value)) {
                $value = filter_var(
                    $value,
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );
            }

            $this->merge([
                $field => $value,
            ]);
        }
    }
}
