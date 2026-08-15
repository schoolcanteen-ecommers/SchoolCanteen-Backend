<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WalletTransaction extends Model
{
    protected $table = 'wallet_transactions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'wallet_id',
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

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function paymentTransaction(): HasOne
    {
        return $this->hasOne(PaymentTransaction::class);
    }
}