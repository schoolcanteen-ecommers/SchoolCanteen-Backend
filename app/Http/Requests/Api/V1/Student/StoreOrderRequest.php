<?php

namespace App\Http\Requests\Api\V1\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchant_id' => [
                'required',
                'uuid',
                'exists:merchants,id',
            ],

            'pickup_slot_id' => [
                'nullable',
                'uuid',
                'exists:pickup_slots,id',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'uuid',
                'distinct',
                'exists:products,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
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
            'merchant_id.required' => 'Merchant wajib dipilih.',
            'merchant_id.uuid' => 'Merchant tidak valid.',
            'merchant_id.exists' => 'Merchant tidak ditemukan.',

            'pickup_slot_id.uuid' => 'Pickup slot tidak valid.',
            'pickup_slot_id.exists' => 'Pickup slot tidak ditemukan.',

            'items.required' => 'Item pesanan wajib diisi.',
            'items.array' => 'Format item pesanan tidak valid.',
            'items.min' => 'Pesanan minimal memiliki satu item.',

            'items.*.product_id.required' => 'Product wajib dipilih.',
            'items.*.product_id.uuid' => 'Product tidak valid.',
            'items.*.product_id.distinct' => 'Product tidak boleh duplikat.',
            'items.*.product_id.exists' => 'Product tidak ditemukan.',

            'items.*.quantity.required' => 'Jumlah product wajib diisi.',
            'items.*.quantity.integer' => 'Jumlah product harus berupa angka.',
            'items.*.quantity.min' => 'Jumlah product minimal 1.',

            'notes.string' => 'Catatan harus berupa teks.',
            'notes.max' => 'Catatan maksimal 500 karakter.',
        ];
    }
}