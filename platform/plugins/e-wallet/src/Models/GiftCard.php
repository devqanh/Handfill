<?php

namespace Botble\EWallet\Models;

use Botble\ACL\Models\User;
use Botble\Base\Models\BaseModel;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Order;
use Botble\EWallet\Enums\GiftCardStatusEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class GiftCard extends BaseModel
{
    protected $table = 'ec_gift_cards';

    protected $fillable = [
        'code',
        'initial_value',
        'balance',
        'currency_code',
        'status',
        'customer_id',
        'purchased_by_customer_id',
        'recipient_email',
        'recipient_name',
        'gift_message',
        'purchase_order_id',
        'issued_by',
        'redeemed_by_customer_id',
        'activated_at',
        'redeemed_at',
        'expires_at',
        'note',
        'metadata',
    ];

    protected $casts = [
        'initial_value' => 'integer',
        'balance' => 'integer',
        'status' => GiftCardStatusEnum::class,
        'metadata' => 'array',
        'activated_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'redeemed_by_customer_id');
    }

    public function issuedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function purchasedBy(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'purchased_by_customer_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'purchase_order_id');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(WalletTransaction::class, 'reference');
    }

    public function isActive(): bool
    {
        return $this->status->getValue() === GiftCardStatusEnum::ACTIVE;
    }

    public function isExpired(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    public function isRedeemable(): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        if ($this->balance <= 0) {
            return false;
        }

        if ($this->status === GiftCardStatusEnum::CANCELLED) {
            return false;
        }

        if ($this->status === GiftCardStatusEnum::EXPIRED) {
            return false;
        }

        if ($this->status === GiftCardStatusEnum::PENDING) {
            return false;
        }

        return true;
    }

    public function getGiftCardCurrencyAttribute()
    {
        return get_all_currencies()->firstWhere('title', $this->currency_code);
    }

    public function getFormattedBalanceAttribute(): string
    {
        return format_price($this->balance / 100, $this->gift_card_currency);
    }

    public function getFormattedInitialValueAttribute(): string
    {
        return format_price($this->initial_value / 100, $this->gift_card_currency);
    }

    public function getMaskedCodeAttribute(): string
    {
        $length = strlen($this->code);

        if ($length <= 8) {
            return $this->code;
        }

        return substr($this->code, 0, 4) . str_repeat('*', $length - 8) . substr($this->code, -4);
    }
}
