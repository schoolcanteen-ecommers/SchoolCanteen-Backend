<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateMerchantStatusRequest;
use App\Http\Resources\AdminMerchantResource;
use App\Models\Merchant;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    public function index(Request $request)
    {
        $query = Merchant::query()
            ->with([
                'owner:id,name,phone',
                'wallet',
            ])
            ->withCount([
                'orders',
                'products',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->query('search')
            );

            $query->where(function ($query) use ($search) {
                $query
                    ->where(
                        'name',
                        'ilike',
                        '%' . $search . '%'
                    )
                    ->orWhereHas(
                        'owner',
                        function ($ownerQuery) use ($search) {
                            $ownerQuery->where(
                                'name',
                                'ilike',
                                '%' . $search . '%'
                            );
                        }
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Type Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('type')) {
            $query->where(
                'type',
                $request->query('type')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        $status = $request->query('status');

        if ($status === 'active') {
            $query->where('is_active', true);
        }

        if ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $merchants = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return AdminMerchantResource::collection(
            $merchants
        )->additional([
            'success' => true,
        ]);
    }

    public function show(Merchant $merchant)
    {
        $merchant->load([
            'owner:id,name,phone',
            'wallet',
            'paymentAccounts' => function ($query) {
                $query
                    ->orderByDesc('is_default')
                    ->orderBy('provider');
            },
        ]);

        $merchant->loadCount([
            'orders',
            'products',
        ]);

        return response()->json([
            'success' => true,
            'data' => new AdminMerchantResource(
                $merchant
            ),
        ]);
    }

    public function updateStatus(
        UpdateMerchantStatusRequest $request,
        Merchant $merchant
    ) {
        $merchant->is_active =
            (bool) $request->validated('is_active');

        $merchant->save();

        $merchant->load([
            'owner:id,name,phone',
            'wallet',
        ]);

        $merchant->loadCount([
            'orders',
            'products',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                $merchant->is_active
                    ? 'Merchant berhasil diaktifkan.'
                    : 'Merchant berhasil dinonaktifkan.',

            'data' =>
                new AdminMerchantResource($merchant),
        ]);
    }
}