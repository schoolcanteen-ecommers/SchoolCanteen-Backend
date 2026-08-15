<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $table = 'payment_transactions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'wallet_transaction_id',
        'provider',
        'provider_order_id',
        'provider_transaction_id',
        'payment_type',
        'status',
        'gross_amount',
        'provider_response',
        'paid_at',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'integer',
            'provider_response' => 'array',
            'paid_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }
}