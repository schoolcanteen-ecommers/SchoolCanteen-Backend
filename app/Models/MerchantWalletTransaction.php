<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantWalletTransaction extends Model
{
    protected $table = 'merchant_wallet_transactions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'merchant_wallet_id',
        'type',
        'direction',
        'amount',
        'status',
        'reference_type',
        'reference_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    public function merchantWallet(): BelongsTo
    {
        return $this->belongsTo(MerchantWallet::class);
    }
}