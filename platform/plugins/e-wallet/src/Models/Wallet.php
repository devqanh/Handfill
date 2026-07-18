<?php

namespace Botble\EWallet\Models;

use Botble\Base\Models\BaseModel;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Helpers\WalletHelper;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends BaseModel
{
    protected $table = 'ec_wallets';

    protected $fillable = [
        'customer_id',
        'balance',
        'currency_code',
    ];

    protected $casts = [
        'balance' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function getWalletCurrencyAttribute()
    {
        return get_all_currencies()->firstWhere('title', $this->currency_code);
    }

    public function getFormattedBalanceAttribute(): string
    {
        return format_price($this->balance / 100, $this->wallet_currency);
    }

    public function hasSufficientBalance(int $amountCents): bool
    {
        $allowNegative = app(WalletHelper::class)->allowNegativeBalance();

        return $allowNegative || $this->balance >= $amountCents;
    }
}
