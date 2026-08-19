<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductModifierGroup extends Model
{
    protected $table =
        'product_modifier_groups';

    protected $keyType =
        'string';

    public $incrementing =
        false;

    protected $fillable = [
        'product_id',
        'name',
        'selection_type',
        'is_required',
        'min_select',
        'max_select',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_required' =>
                'boolean',

            'min_select' =>
                'integer',

            'max_select' =>
                'integer',

            'sort_order' =>
                'integer',

            'is_active' =>
                'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class
        );
    }

    public function options(): HasMany
    {
        return $this
            ->hasMany(
                ProductModifierOption::class,
                'modifier_group_id'
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'id'
            );
    }
}
