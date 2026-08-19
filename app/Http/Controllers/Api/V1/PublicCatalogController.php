<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PublicCatalogRequest;
use App\Services\PublicCatalogReadService;
use Illuminate\Http\JsonResponse;

class PublicCatalogController extends Controller
{
    public function __construct(
        private readonly
        PublicCatalogReadService $catalog
    ) {
    }

    public function home(): JsonResponse
    {
        return response()->json([
            'success' => true,

            'data' =>
                $this->catalog->home(),
        ]);
    }

    public function catalog(
        PublicCatalogRequest $request
    ): JsonResponse {
        $type =
            $request->validated(
                'type'
            );

        return response()->json([
            'success' => true,

            'data' =>
                $this->catalog->catalog(
                    $type
                ),
        ]);
    }
}
