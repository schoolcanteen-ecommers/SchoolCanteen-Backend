<?php

namespace App\Http\Controllers\Api\V1\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionSummaryController extends Controller
{
    public function index(Request $request)
    {
        $profile =
            $request->attributes->get(
                'profile'
            );

        $merchant =
            Merchant::query()
                ->where(
                    'owner_user_id',
                    $profile->id
                )
                ->first();

        if (!$merchant) {
            return response()->json([
                'success' =>
                    false,

                'error' => [
                    'code' =>
                        'MERCHANT_NOT_FOUND',

                    'message' =>
                        'Merchant tidak ditemukan.',
                ],
            ], 404);
        }

        /*
         * Source of truth production:
         *
         * confirmed
         * preparing
         *
         * READY sudah keluar dari kebutuhan
         * produksi.
         */
        $items =
            OrderItem::query()
                ->selectRaw('
                    product_id,
                    product_name,
                    SUM(quantity) as total_quantity,
                    COUNT(DISTINCT order_id) as order_count
                ')
                ->whereHas(
                    'order',
                    function ($query) use (
                        $merchant
                    ) {
                        $query
                            ->where(
                                'merchant_id',
                                $merchant->id
                            )
                            ->whereIn(
                                'status',
                                [
                                    'confirmed',
                                    'preparing',
                                ]
                            );
                    }
                )
                ->groupBy([
                    'product_id',
                    'product_name',
                ])
                ->orderBy(
                    'product_name'
                )
                ->get();

        /*
         * Modifier menggunakan snapshot
         * OrderItemModifier, bukan live
         * ProductModifier.
         *
         * quantity yang dijumlahkan adalah
         * quantity OrderItem karena satu
         * modifier selection berlaku untuk
         * seluruh quantity pada order line.
         */
        $modifierRows =
            DB::table(
                'order_item_modifiers as modifiers'
            )
                ->join(
                    'order_items as items',
                    'items.id',
                    '=',
                    'modifiers.order_item_id'
                )
                ->join(
                    'orders',
                    'orders.id',
                    '=',
                    'items.order_id'
                )
                ->selectRaw('
                    items.product_id,
                    items.product_name,
                    modifiers.group_name,
                    modifiers.option_name,
                    SUM(items.quantity) as total_quantity
                ')
                ->where(
                    'orders.merchant_id',
                    $merchant->id
                )
                ->whereIn(
                    'orders.status',
                    [
                        'confirmed',
                        'preparing',
                    ]
                )
                ->groupBy([
                    'items.product_id',
                    'items.product_name',
                    'modifiers.group_name',
                    'modifiers.option_name',
                ])
                ->orderBy(
                    'items.product_name'
                )
                ->orderBy(
                    'modifiers.group_name'
                )
                ->orderBy(
                    'modifiers.option_name'
                )
                ->get();

        /*
         * Kelompokkan breakdown modifier
         * berdasarkan produk snapshot.
         */
        $modifierBreakdown =
            $modifierRows
                ->groupBy(
                    fn ($row) =>
                        $this->productKey(
                            $row->product_id,
                            $row->product_name
                        )
                )
                ->map(
                    function ($rows) {
                        return $rows
                            ->groupBy(
                                'group_name'
                            )
                            ->map(
                                function (
                                    $groupRows,
                                    $groupName
                                ) {
                                    return [
                                        'group_name' =>
                                            $groupName,

                                        'options' =>
                                            $groupRows
                                                ->map(
                                                    fn ($row) => [
                                                        'option_name' =>
                                                            $row->option_name,

                                                        'quantity' =>
                                                            (int)
                                                            $row->total_quantity,
                                                    ]
                                                )
                                                ->values()
                                                ->all(),
                                    ];
                                }
                            )
                            ->values()
                            ->all();
                    }
                );

        $products =
            $items
                ->map(
                    function ($item) use (
                        $modifierBreakdown
                    ) {
                        $key =
                            $this->productKey(
                                $item->product_id,
                                $item->product_name
                            );

                        return [
                            'product_id' =>
                                $item->product_id,

                            'product_name' =>
                                $item->product_name,

                            'total_quantity' =>
                                (int)
                                $item->total_quantity,

                            'order_count' =>
                                (int)
                                $item->order_count,

                            'modifier_breakdown' =>
                                $modifierBreakdown
                                    ->get(
                                        $key,
                                        []
                                    ),
                        ];
                    }
                )
                ->values();

        $activeOrderCount =
            Order::query()
                ->where(
                    'merchant_id',
                    $merchant->id
                )
                ->whereIn(
                    'status',
                    [
                        'confirmed',
                        'preparing',
                    ]
                )
                ->count();

        $totalItemCount =
            $items->sum(
                'total_quantity'
            );

        return response()->json([
            'success' =>
                true,

            'data' => [
                'statuses' => [
                    'confirmed',
                    'preparing',
                ],

                'active_order_count' =>
                    $activeOrderCount,

                'total_item_count' =>
                    (int)
                    $totalItemCount,

                'products' =>
                    $products,
            ],
        ]);
    }

    private function productKey(
        ?string $productId,
        string $productName
    ): string {
        return sprintf(
            '%s|%s',
            $productId ?? 'null',
            $productName
        );
    }
}
