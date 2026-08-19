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

            /*
             * Product ID sengaja TIDAK distinct.
             *
             * Product yang sama dapat muncul
             * sebagai line berbeda apabila
             * modifier / catatannya berbeda.
             */
            'items.*.product_id' => [
                'required',
                'uuid',
                'exists:products,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            /*
             * Tidak menggunakan exists di sini.
             *
             * Ownership + active state modifier
             * divalidasi di dalam transaction
             * menggunakan row yang dikunci.
             */
            'items.*.modifier_option_ids' => [
                'sometimes',
                'array',
                'max:50',
            ],

            'items.*.modifier_option_ids.*' => [
                'required',
                'uuid',
                'distinct',
            ],

            'items.*.notes' => [
                'nullable',
                'string',
                'max:120',
            ],

            /*
             * Order-level note existing tetap
             * dipertahankan untuk kompatibilitas.
             */
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
            'merchant_id.required' =>
                'Merchant wajib dipilih.',

            'merchant_id.uuid' =>
                'Merchant tidak valid.',

            'merchant_id.exists' =>
                'Merchant tidak ditemukan.',

            'pickup_slot_id.uuid' =>
                'Pickup slot tidak valid.',

            'pickup_slot_id.exists' =>
                'Pickup slot tidak ditemukan.',

            'items.required' =>
                'Item pesanan wajib diisi.',

            'items.array' =>
                'Format item pesanan tidak valid.',

            'items.min' =>
                'Pesanan minimal memiliki satu item.',

            'items.*.product_id.required' =>
                'Product wajib dipilih.',

            'items.*.product_id.uuid' =>
                'Product tidak valid.',

            'items.*.product_id.exists' =>
                'Product tidak ditemukan.',

            'items.*.quantity.required' =>
                'Jumlah product wajib diisi.',

            'items.*.quantity.integer' =>
                'Jumlah product harus berupa angka.',

            'items.*.quantity.min' =>
                'Jumlah product minimal 1.',

            'items.*.modifier_option_ids.array' =>
                'Format pilihan product tidak valid.',

            'items.*.modifier_option_ids.max' =>
                'Pilihan product terlalu banyak.',

            'items.*.modifier_option_ids.*.uuid' =>
                'Pilihan product tidak valid.',

            'items.*.modifier_option_ids.*.distinct' =>
                'Pilihan product tidak boleh duplikat.',

            'items.*.notes.string' =>
                'Catatan item harus berupa teks.',

            'items.*.notes.max' =>
                'Catatan item maksimal 120 karakter.',

            'notes.string' =>
                'Catatan harus berupa teks.',

            'notes.max' =>
                'Catatan maksimal 500 karakter.',
        ];
    }
}
