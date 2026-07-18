<?php

namespace Botble\EWallet\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Models\BaseModel;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Enums\PayoutPaymentMethodsEnum;
use Botble\EWallet\Enums\WithdrawalStatusEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

class Withdrawal extends BaseModel
{
    protected $table = 'ec_wallet_withdrawals';

    protected $fillable = [
        'wallet_id',
        'customer_id',
        'amount',
        'currency_code',
        'status',
        'payment_channel',
        'payment_details',
        'bank_info',
        'notes',
        'transaction_id',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'status' => WithdrawalStatusEnum::class,
        'payment_channel' => PayoutPaymentMethodsEnum::class,
        'payment_details' => SafeContent::class,
        'bank_info' => 'array',
        'notes' => SafeContent::class,
        'processed_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withDefault();
    }

    public function getWithdrawalCurrencyAttribute()
    {
        return get_all_currencies()->firstWhere('title', $this->currency_code);
    }

    public function getFormattedAmountAttribute(): string
    {
        return format_price($this->amount / 100, $this->withdrawal_currency);
    }

    public function canEditStatus(): bool
    {
        return in_array($this->status->getValue(), [
            WithdrawalStatusEnum::PENDING,
            WithdrawalStatusEnum::PROCESSING,
        ]);
    }

    public function getNextStatuses(): array
    {
        return match ($this->status->getValue()) {
            WithdrawalStatusEnum::PENDING => Arr::except(
                WithdrawalStatusEnum::labels(),
                WithdrawalStatusEnum::CANCELLED
            ),
            WithdrawalStatusEnum::PROCESSING => Arr::except(
                WithdrawalStatusEnum::labels(),
                [WithdrawalStatusEnum::PENDING, WithdrawalStatusEnum::CANCELLED]
            ),
            default => [$this->status->getValue() => $this->status->label()],
        };
    }
}
