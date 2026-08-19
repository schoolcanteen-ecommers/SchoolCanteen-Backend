<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemModifier extends Model
{
    protected $table =
        'order_item_modifiers';

    protected $keyType =
        'string';

    public $incrementing =
        false;

    protected $fillable = [
        'order_item_id',
        'modifier_group_id',
        'modifier_option_id',
        'group_name',
        'option_name',
        'price_delta',
    ];

    protected function casts(): array
    {
        return [
            'price_delta' =>
                'integer',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(
            OrderItem::class
        );
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(
            ProductModifierGroup::class,
            'modifier_group_id'
        );
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(
            ProductModifierOption::class,
            'modifier_option_id'
        );
    }
}
