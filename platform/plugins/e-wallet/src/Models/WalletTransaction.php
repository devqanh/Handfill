<?php

namespace Botble\EWallet\Models;

use Botble\Base\Models\BaseModel;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Enums\TransactionStatusEnum;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends BaseModel
{
    protected $table = 'ec_wallet_transactions';

    protected $fillable = [
        'wallet_id',
        'customer_id',
        'currency_code',
        'type',
        'status',
        'amount',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'created_by',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array',
        'type' => TransactionTypeEnum::class,
        'status' => TransactionStatusEnum::class,
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    public function isDebit(): bool
    {
        return $this->amount < 0;
    }

    public function getTransactionCurrencyAttribute()
    {
        $currencyCode = $this->currency_code ?? $this->wallet?->currency_code;

        return get_all_currencies()->firstWhere('title', $currencyCode);
    }

    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->isCredit() ? '+' : '';

        return $prefix . format_price(abs($this->amount) / 100, $this->transaction_currency);
    }

    public function getFormattedBalanceBeforeAttribute(): string
    {
        return format_price($this->balance_before / 100, $this->transaction_currency);
    }

    public function getFormattedBalanceAfterAttribute(): string
    {
        return format_price($this->balance_after / 100, $this->transaction_currency);
    }
}
