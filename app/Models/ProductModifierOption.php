<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductModifierOption extends Model
{
    protected $table =
        'product_modifier_options';

    protected $keyType =
        'string';

    public $incrementing =
        false;

    protected $fillable = [
        'modifier_group_id',
        'name',
        'price_delta',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_delta' =>
                'integer',

            'sort_order' =>
                'integer',

            'is_active' =>
                'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(
            ProductModifierGroup::class,
            'modifier_group_id'
        );
    }
}
