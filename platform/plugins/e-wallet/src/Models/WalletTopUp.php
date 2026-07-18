<?php

namespace Botble\EWallet\Models;

use Botble\Base\Models\BaseModel;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Enums\TopUpStatusEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTopUp extends BaseModel
{
    protected $table = 'ec_wallet_topups';

    protected $fillable = [
        'customer_id',
        'wallet_id',
        'code',
        'amount',
        'currency_code',
        'converted_amount',
        'wallet_currency_code',
        'exchange_rate',
        'status',
        'payment_id',
        'payment_method',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'converted_amount' => 'integer',
        'exchange_rate' => 'decimal:8',
        'metadata' => 'array',
        'status' => TopUpStatusEnum::class,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function isPending(): bool
    {
        return $this->status->getValue() === TopUpStatusEnum::PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status->getValue() === TopUpStatusEnum::COMPLETED;
    }

    public function getTopUpCurrencyAttribute()
    {
        return get_all_currencies()->firstWhere('title', $this->currency_code);
    }

    public function getWalletCurrencyAttribute()
    {
        return get_all_currencies()->firstWhere('title', $this->wallet_currency_code);
    }

    public function getFormattedAmountAttribute(): string
    {
        return format_price($this->amount / 100, $this->topup_currency);
    }

    public function getFormattedConvertedAmountAttribute(): string
    {
        return format_price($this->converted_amount / 100, $this->wallet_currency);
    }
}
