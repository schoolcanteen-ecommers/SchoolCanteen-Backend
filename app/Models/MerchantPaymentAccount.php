<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantPaymentAccount extends Model
{
    protected $table = 'merchant_payment_accounts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'merchant_id',
        'type',
        'provider',
        'account_number',
        'account_name',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class, 'payment_account_id');
    }
}