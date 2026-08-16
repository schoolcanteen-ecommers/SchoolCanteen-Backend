<?php

namespace App\Http\Controllers\Api\V1\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantCategoryResource;
use App\Models\Category;
use App\Models\Merchant;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $profile = $request->attributes->get('profile');

        $merchant = Merchant::query()
            ->where('owner_user_id', $profile->id)
            ->first();

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MERCHANT_NOT_FOUND',
                    'message' => 'Merchant tidak ditemukan.',
                ],
            ], 404);
        }

        $categories = Category::query()
            ->where('merchant_id', $merchant->id)
            ->orderBy('name')
            ->get();

        return MerchantCategoryResource::collection($categories)
            ->additional([
                'success' => true,
            ]);
    }
}