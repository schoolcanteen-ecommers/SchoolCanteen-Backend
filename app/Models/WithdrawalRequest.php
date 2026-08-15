<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
{
    protected $table = 'withdrawal_requests';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'merchant_id',
        'payment_account_id',
        'approved_by',
        'amount',
        'method',
        'status',
        'notes',
        'approved_at',
        'processed_at',
        'completed_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
            'completed_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(MerchantPaymentAccount::class, 'payment_account_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'approved_by');
    }
}