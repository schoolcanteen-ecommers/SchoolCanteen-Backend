<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Student\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use App\Models\EscrowTransaction;
use App\Models\Merchant;
use App\Models\MerchantWallet;
use App\Models\MerchantWalletTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\ProductModifierGroup;
use App\Models\ProductModifierOption;
use App\Models\Pickup;
use App\Models\PickupSlot;
use App\Models\Product;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $profile = $request->attributes->get('profile');

        $orders = Order::query()
            ->where('user_id', $profile->id)
            ->with([
                'merchant',
                'pickupSlot',
                'pickup',
                'items.modifiers',
                'escrow',
            ])
            ->latest()
            ->paginate(10);

        return OrderResource::collection($orders)
            ->additional([
                'success' => true,
            ]);
    }

    public function show(Request $request, string $order)
    {
        $profile = $request->attributes->get('profile');

        $studentOrder = Order::query()
            ->where('user_id', $profile->id)
            ->whereKey($order)
            ->with([
                'merchant',
                'pickupSlot',
                'pickup',
                'items.modifiers',
                'escrow',
            ])
            ->first();

        if (!$studentOrder) {
            $this->fail(
                'ORDER_NOT_FOUND',
                'Pesanan tidak ditemukan.',
                404
            );
        }

        return response()->json([
            'success' => true,
            'data' => new OrderResource($studentOrder),
        ]);
    }
    
    public function store(StoreOrderRequest $request)
    {
        $profile = $request->attributes->get('profile');
        $data = $request->validated();

        $result = DB::transaction(function () use ($profile, $data) {

            /*
            |--------------------------------------------------------------------------
            | Merchant
            |--------------------------------------------------------------------------
            */

            $merchant = Merchant::query()
                ->whereKey($data['merchant_id'])
                ->lockForUpdate()
                ->first();

            if (!$merchant) {
                $this->fail(
                    'MERCHANT_NOT_FOUND',
                    'Merchant tidak ditemukan.',
                    404
                );
            }

            if (!$merchant->is_active) {
                $this->fail(
                    'MERCHANT_INACTIVE',
                    'Merchant sedang tidak aktif.'
                );
            }

            if (!$merchant->is_open) {
                $this->fail(
                    'MERCHANT_CLOSED',
                    'Merchant sedang tutup.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Student Wallet
            |--------------------------------------------------------------------------
            */

            $wallet = Wallet::query()
                ->where('user_id', $profile->id)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                $this->fail(
                    'WALLET_NOT_FOUND',
                    'Wallet pengguna tidak ditemukan.',
                    404
                );
            }

            if (!$wallet->is_active) {
                $this->fail(
                    'WALLET_INACTIVE',
                    'Wallet pengguna sedang tidak aktif.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Merchant Wallet
            |--------------------------------------------------------------------------
            */

            $merchantWallet = MerchantWallet::query()
                ->where('merchant_id', $merchant->id)
                ->lockForUpdate()
                ->first();

            if (!$merchantWallet) {
                $this->fail(
                    'MERCHANT_WALLET_NOT_FOUND',
                    'Wallet merchant tidak ditemukan.',
                    404
                );
            }

            if (!$merchantWallet->is_active) {
                $this->fail(
                    'MERCHANT_WALLET_INACTIVE',
                    'Wallet merchant sedang tidak aktif.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Products + Aggregate Stock
            |--------------------------------------------------------------------------
            */

            /*
             * Product yang sama dapat muncul
             * sebagai beberapa order line.
             *
             * Lock tetap dilakukan hanya satu
             * kali per unique product dan selalu
             * dalam urutan ID deterministic.
             */
            $productIds = collect(
                $data['items']
            )
                ->pluck('product_id')
                ->unique()
                ->sort()
                ->values()
                ->all();

            /*
             * Quantity harus digabung per product
             * sebelum stock validation.
             *
             * Contoh:
             *
             * Mie Pedas       x2
             * Mie Tidak Pedas x2
             *
             * requested stock Mie = 4.
             */
            $requestedQuantities = collect(
                $data['items']
            )
                ->groupBy('product_id')
                ->map(
                    fn ($lines) =>
                        (int)
                        $lines->sum(
                            'quantity'
                        )
                );

            $products = Product::query()
                ->whereIn(
                    'id',
                    $productIds
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            /*
             * Validate product + aggregate stock
             * sekali per unique product.
             */
            foreach (
                $productIds as $productId
            ) {
                $product =
                    $products->get(
                        $productId
                    );

                if (!$product) {
                    $this->fail(
                        'PRODUCT_NOT_FOUND',
                        'Produk tidak ditemukan.',
                        404
                    );
                }

                if (
                    $product->merchant_id
                    !== $merchant->id
                ) {
                    $this->fail(
                        'DIFFERENT_MERCHANT',
                        'Semua produk harus berasal dari merchant yang sama.'
                    );
                }

                if (!$product->is_active) {
                    $this->fail(
                        'PRODUCT_INACTIVE',
                        "Produk {$product->name} sedang tidak tersedia."
                    );
                }

                $requestedQuantity =
                    (int)
                    $requestedQuantities
                        ->get(
                            $productId,
                            0
                        );

                if (
                    $product->stock
                    < $requestedQuantity
                ) {
                    $this->fail(
                        'INSUFFICIENT_STOCK',
                        "Stok {$product->name} telah berubah dan tidak mencukupi.",
                        409,
                        [
                            'product_id' =>
                                $product->id,

                            'product_name' =>
                                $product->name,

                            'requested_quantity' =>
                                $requestedQuantity,

                            'available_quantity' =>
                                (int)
                                $product->stock,
                        ]
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Modifier Configuration Locks
            |--------------------------------------------------------------------------
            |
            | Semua konfigurasi modifier untuk
            | product checkout dikunci setelah
            | product rows.
            |
            | Lock order:
            |
            | products
            | -> modifier groups
            | -> modifier options
            |
            */

            $modifierGroups =
                ProductModifierGroup::query()
                    ->whereIn(
                        'product_id',
                        $productIds
                    )
                    ->orderBy(
                        'product_id'
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

            $modifierGroupIds =
                $modifierGroups
                    ->pluck('id')
                    ->sort()
                    ->values()
                    ->all();

            $modifierOptions =
                $modifierGroupIds === []
                    ? collect()
                    : ProductModifierOption::query()
                        ->whereIn(
                            'modifier_group_id',
                            $modifierGroupIds
                        )
                        ->orderBy(
                            'modifier_group_id'
                        )
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

            $groupsByProduct =
                $modifierGroups
                    ->groupBy(
                        'product_id'
                    );

            $optionsByGroup =
                $modifierOptions
                    ->groupBy(
                        'modifier_group_id'
                    );

            /*
            |--------------------------------------------------------------------------
            | Resolve Order Lines
            |--------------------------------------------------------------------------
            */

            $resolvedItems = [];
            $totalAmount = 0;

            foreach (
                $data['items'] as $item
            ) {
                $product =
                    $products->get(
                        $item['product_id']
                    );

                $productGroups =
                    $groupsByProduct->get(
                        $product->id,
                        collect()
                    );

                $productGroupIds =
                    $productGroups
                        ->pluck('id')
                        ->all();

                $productOptions =
                    $modifierOptions
                        ->whereIn(
                            'modifier_group_id',
                            $productGroupIds
                        )
                        ->keyBy('id');

                $selectedOptionIds =
                    collect(
                        $item[
                            'modifier_option_ids'
                        ] ?? []
                    )->values();

                $selectedOptions =
                    collect();

                /*
                 * Pertama validasi bahwa setiap
                 * option benar-benar berada pada
                 * product ini dan masih usable.
                 */
                foreach (
                    $selectedOptionIds
                    as $selectedOptionId
                ) {
                    $option =
                        $productOptions->get(
                            $selectedOptionId
                        );

                    if (!$option) {
                        $this->fail(
                            'MODIFIER_OPTION_INVALID',
                            "Pilihan untuk {$product->name} tidak valid.",
                            409,
                            [
                                'product_id' =>
                                    $product->id,

                                'modifier_option_id' =>
                                    $selectedOptionId,
                            ]
                        );
                    }

                    $group =
                        $productGroups
                            ->firstWhere(
                                'id',
                                $option
                                    ->modifier_group_id
                            );

                    if (
                        !$group
                        || !$group->is_active
                        || !$option->is_active
                    ) {
                        $this->fail(
                            'MODIFIER_OPTION_UNAVAILABLE',
                            "Salah satu pilihan untuk {$product->name} sudah tidak tersedia.",
                            409,
                            [
                                'product_id' =>
                                    $product->id,

                                'modifier_group_id' =>
                                    $group?->id,

                                'modifier_group_name' =>
                                    $group?->name,

                                'modifier_option_id' =>
                                    $selectedOptionId,
                            ]
                        );
                    }

                    $selectedOptions->push(
                        $option
                    );
                }

                $modifierSnapshots = [];
                $modifierDelta = 0;

                /*
                 * Kemudian validate selection
                 * rule setiap active group.
                 */
                foreach (
                    $productGroups
                    as $group
                ) {
                    if (!$group->is_active) {
                        continue;
                    }

                    $groupOptions =
                        $optionsByGroup->get(
                            $group->id,
                            collect()
                        );

                    $activeOptions =
                        $groupOptions
                            ->where(
                                'is_active',
                                true
                            )
                            ->values();

                    if (
                        $group->selection_type
                        === 'single'
                    ) {
                        $minimum =
                            $group->is_required
                                ? 1
                                : 0;

                        $maximum = 1;
                    } else {
                        $minimum =
                            $group->is_required
                                ? max(
                                    1,
                                    (int)
                                    $group
                                        ->min_select
                                )
                                : 0;

                        $maximum =
                            max(
                                $minimum,
                                (int)
                                $group
                                    ->max_select,
                                1
                            );
                    }

                    /*
                     * Required group yang tidak
                     * memiliki cukup active option
                     * adalah invalid merchant
                     * configuration.
                     */
                    if (
                        $group->is_required
                        && $activeOptions->count()
                            < $minimum
                    ) {
                        $this->fail(
                            'MODIFIER_CONFIGURATION_UNAVAILABLE',
                            "Pilihan wajib untuk {$product->name} sedang tidak tersedia.",
                            409,
                            [
                                'product_id' =>
                                    $product->id,

                                'modifier_group_id' =>
                                    $group->id,

                                'modifier_group_name' =>
                                    $group->name,

                                'min_select' =>
                                    $minimum,

                                'active_options_count' =>
                                    $activeOptions
                                        ->count(),
                            ]
                        );
                    }

                    $selectedForGroup =
                        $selectedOptions
                            ->where(
                                'modifier_group_id',
                                $group->id
                            )
                            ->values();

                    $selectedCount =
                        $selectedForGroup
                            ->count();

                    if (
                        $selectedCount
                        < $minimum
                    ) {
                        $this->fail(
                            'MODIFIER_SELECTION_REQUIRED',
                            "Pilihan {$group->name} untuk {$product->name} belum lengkap.",
                            409,
                            [
                                'product_id' =>
                                    $product->id,

                                'modifier_group_id' =>
                                    $group->id,

                                'modifier_group_name' =>
                                    $group->name,

                                'selected_count' =>
                                    $selectedCount,

                                'min_select' =>
                                    $minimum,

                                'max_select' =>
                                    $maximum,
                            ]
                        );
                    }

                    if (
                        $selectedCount
                        > $maximum
                    ) {
                        $this->fail(
                            'MODIFIER_SELECTION_INVALID',
                            "Pilihan {$group->name} untuk {$product->name} melebihi batas.",
                            409,
                            [
                                'product_id' =>
                                    $product->id,

                                'modifier_group_id' =>
                                    $group->id,

                                'modifier_group_name' =>
                                    $group->name,

                                'selected_count' =>
                                    $selectedCount,

                                'min_select' =>
                                    $minimum,

                                'max_select' =>
                                    $maximum,
                            ]
                        );
                    }

                    foreach (
                        $selectedForGroup
                        as $option
                    ) {
                        $priceDelta =
                            (int)
                            $option
                                ->price_delta;

                        $modifierDelta +=
                            $priceDelta;

                        $modifierSnapshots[] = [
                            'modifier_group_id' =>
                                $group->id,

                            'modifier_option_id' =>
                                $option->id,

                            'group_name' =>
                                $group->name,

                            'option_name' =>
                                $option->name,

                            'price_delta' =>
                                $priceDelta,
                        ];
                    }
                }

                /*
                 * Final unit price hanya dihitung
                 * dari data database.
                 *
                 * Frontend tidak pernah dipercaya
                 * untuk harga modifier.
                 */
                $unitPrice =
                    (int) $product->price
                    + $modifierDelta;

                $quantity =
                    (int)
                    $item['quantity'];

                $subtotal =
                    $unitPrice
                    * $quantity;

                $totalAmount +=
                    $subtotal;

                $resolvedItems[] = [
                    'product_id' =>
                        $product->id,

                    'product_name' =>
                        $product->name,

                    'product_image_url' =>
                        $product->image_url,

                    'unit_price' =>
                        $unitPrice,

                    'quantity' =>
                        $quantity,

                    'subtotal' =>
                        $subtotal,

                    'notes' =>
                        $item['notes']
                            ?? null,

                    'modifiers' =>
                        $modifierSnapshots,
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | Pickup Slot
            |--------------------------------------------------------------------------
            */

            $pickupSlot = null;

            if (!empty($data['pickup_slot_id'])) {
                $pickupSlot = PickupSlot::query()
                    ->whereKey($data['pickup_slot_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$pickupSlot) {
                    $this->fail(
                        'PICKUP_SLOT_NOT_FOUND',
                        'Pickup slot tidak ditemukan.',
                        404
                    );
                }

                if ($pickupSlot->merchant_id !== $merchant->id) {
                    $this->fail(
                        'INVALID_PICKUP_SLOT',
                        'Pickup slot tidak sesuai dengan merchant.'
                    );
                }

                if (!$pickupSlot->is_active) {
                    $this->fail(
                        'PICKUP_SLOT_INACTIVE',
                        'Pickup slot sedang tidak tersedia.'
                    );
                }

                $usedCapacity = Order::query()
                    ->where('pickup_slot_id', $pickupSlot->id)
                    ->where('status', '!=', 'cancelled')
                    ->count();

                if ($usedCapacity >= $pickupSlot->capacity) {
                    $this->fail(
                        'PICKUP_SLOT_FULL',
                        'Pickup slot sudah penuh.'
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Balance
            |--------------------------------------------------------------------------
            */

            if ($wallet->balance < $totalAmount) {
                $this->fail(
                    'INSUFFICIENT_BALANCE',
                    'Saldo wallet tidak mencukupi.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Order
            |--------------------------------------------------------------------------
            */

            $order = new Order();

            $order->id = Str::uuid()->toString();
            $order->user_id = $profile->id;
            $order->merchant_id = $merchant->id;
            $order->pickup_slot_id = $pickupSlot?->id;
            $order->order_code = $this->generateOrderCode();
            $order->status = 'waiting';
            $order->total_amount = $totalAmount;
            $order->notes = $data['notes'] ?? null;

            $order->save();


            /*
            |--------------------------------------------------------------------------
            | Order Items + Modifier Snapshots
            |--------------------------------------------------------------------------
            */

            foreach (
                $resolvedItems as $item
            ) {
                $orderItem =
                    new OrderItem();

                $orderItem->id =
                    Str::uuid()
                        ->toString();

                $orderItem->order_id =
                    $order->id;

                $orderItem->product_id =
                    $item['product_id'];

                $orderItem->product_name =
                    $item['product_name'];

                $orderItem->product_image_url =
                    $item[
                        'product_image_url'
                    ];

                /*
                 * unit_price adalah FINAL unit
                 * price setelah modifier.
                 */
                $orderItem->unit_price =
                    $item['unit_price'];

                $orderItem->quantity =
                    $item['quantity'];

                $orderItem->subtotal =
                    $item['subtotal'];

                $orderItem->notes =
                    $item['notes'];

                $orderItem->save();

                foreach (
                    $item['modifiers']
                    as $modifier
                ) {
                    $snapshot =
                        new OrderItemModifier();

                    $snapshot->id =
                        Str::uuid()
                            ->toString();

                    $snapshot->order_item_id =
                        $orderItem->id;

                    $snapshot->modifier_group_id =
                        $modifier[
                            'modifier_group_id'
                        ];

                    $snapshot->modifier_option_id =
                        $modifier[
                            'modifier_option_id'
                        ];

                    $snapshot->group_name =
                        $modifier[
                            'group_name'
                        ];

                    $snapshot->option_name =
                        $modifier[
                            'option_name'
                        ];

                    $snapshot->price_delta =
                        $modifier[
                            'price_delta'
                        ];

                    $snapshot->save();
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Aggregate Stock Decrement
            |--------------------------------------------------------------------------
            |
            | Product yang sama hanya dikurangi
            | satu kali setelah seluruh line
            | berhasil dibuat.
            |
            */

            foreach (
                $requestedQuantities
                as $productId => $quantity
            ) {
                $product =
                    $products->get(
                        $productId
                    );

                $product->stock -=
                    (int) $quantity;

                $product->save();
            }


            /*
            |--------------------------------------------------------------------------
            | Student Wallet
            |--------------------------------------------------------------------------
            */

            $wallet->balance -= $totalAmount;
            $wallet->save();

            $walletTransaction = new WalletTransaction();

            $walletTransaction->id = Str::uuid()->toString();
            $walletTransaction->wallet_id = $wallet->id;
            $walletTransaction->type = 'payment';
            $walletTransaction->direction = 'debit';
            $walletTransaction->amount = $totalAmount;
            $walletTransaction->status = 'completed';
            $walletTransaction->reference_type = 'order';
            $walletTransaction->reference_id = $order->id;
            $walletTransaction->description =
                "Pembayaran pesanan {$order->order_code}";

            $walletTransaction->save();


            /*
            |--------------------------------------------------------------------------
            | Escrow
            |--------------------------------------------------------------------------
            */

            $escrow = new EscrowTransaction();

            $escrow->id = Str::uuid()->toString();
            $escrow->order_id = $order->id;
            $escrow->amount = $totalAmount;
            $escrow->status = 'held';
            $escrow->held_at = now();

            $escrow->save();


            /*
            |--------------------------------------------------------------------------
            | Merchant Pending Balance
            |--------------------------------------------------------------------------
            */

            $merchantWallet->pending_balance += $totalAmount;
            $merchantWallet->save();

            $merchantTransaction = new MerchantWalletTransaction();

            $merchantTransaction->id = Str::uuid()->toString();
            $merchantTransaction->merchant_wallet_id = $merchantWallet->id;
            $merchantTransaction->type = 'order_pending';
            $merchantTransaction->direction = 'credit';
            $merchantTransaction->amount = $totalAmount;
            $merchantTransaction->status = 'completed';
            $merchantTransaction->reference_type = 'order';
            $merchantTransaction->reference_id = $order->id;
            $merchantTransaction->description =
                "Dana pending pesanan {$order->order_code}";

            $merchantTransaction->save();


            /*
            |--------------------------------------------------------------------------
            | Pickup
            |--------------------------------------------------------------------------
            */

            $pickup = new Pickup();

            $pickup->id = Str::uuid()->toString();
            $pickup->order_id = $order->id;
            $pickup->pickup_token = Str::uuid()->toString();
            $pickup->pickup_code = $this->generatePickupCode();
            $pickup->status = 'waiting';

            $pickup->save();


            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'status' => $order->status,

                'merchant' => [
                    'id' => $merchant->id,
                    'name' => $merchant->name,
                ],

                'pickup_slot_id' => $pickupSlot?->id,

                'items' => collect($resolvedItems)
                    ->map(fn ($item) => [
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'product_image_url' =>
                            $item['product_image_url'],
                        'unit_price' => $item['unit_price'],
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['subtotal'],
                        'notes' => $item['notes'],
                        'modifiers' => $item['modifiers'],
                    ])
                    ->values()
                    ->all(),

                'total_amount' => $totalAmount,
                'remaining_balance' => $wallet->balance,

                'pickup' => [
                    'token' => $pickup->pickup_token,
                    'code' => $pickup->pickup_code,
                    'status' => $pickup->status,
                ],

                'notes' => $order->notes,
                'created_at' => $order->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dibuat.',
            'data' => $result,
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function generateOrderCode(): string
    {
        do {
            $code = 'SC-'
                . now()->format('Ymd')
                . '-'
                . strtoupper(Str::random(6));
        } while (
            Order::query()
                ->where('order_code', $code)
                ->exists()
        );

        return $code;
    }

    private function generatePickupCode(): string
    {
        do {
            $code = (string) random_int(
                100000,
                999999
            );
        } while (
            Pickup::query()
                ->where('pickup_code', $code)
                ->exists()
        );

        return $code;
    }

    private function fail(
        string $code,
        string $message,
        int $status = 409,
        array $details = []
    ): never {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== []) {
            $error['details'] = $details;
        }

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error' => $error,
            ], $status)
        );
    }
}