<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Merchant extends Model
{
    protected $table = 'merchants';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'owner_user_id',
        'name',
        'type',
        'description',
        'logo_url',
        'is_active',
        'is_open',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_open' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'owner_user_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function pickupSlots(): HasMany
    {
        return $this->hasMany(PickupSlot::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(MerchantWallet::class);
    }

    public function paymentAccounts(): HasMany
    {
        return $this->hasMany(MerchantPaymentAccount::class);
    }
}