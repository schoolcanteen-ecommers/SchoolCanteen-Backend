<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantResource;
use App\Models\Merchant;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    public function index(Request $request)
    {
        $merchants = Merchant::query()
            ->where('is_active', true)
            ->withCount([
                'products' => function ($query) {
                    $query->where('is_active', true);
                },
            ])

            ->when(
                $request->filled('type'),
                fn ($query) =>
                $query->where(
                    'type',
                    $request->type
                )
            )

            ->orderBy('name')
            ->get();

        return MerchantResource::collection($merchants);
    }

    public function show(Merchant $merchant)
    {
        if (!$merchant->is_active) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MERCHANT_NOT_FOUND',
                    'message' => 'Merchant tidak ditemukan.',
                ],
            ], 404);
        }

        $merchant->load([
            'categories',
            'products' => function ($query) {
                $query
                    ->where('is_active', true)
                    ->with('category');
            },
        ]);

        $merchant->loadCount([
            'products' => function ($query) {
                $query->where('is_active', true);
            },
        ]);

        return new MerchantResource($merchant);
    }
}