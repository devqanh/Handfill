<?php

namespace Botble\HandmadeWorkflow\Models;

use Botble\Base\Models\BaseModel;
use Botble\Ecommerce\Models\Order;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderQuote extends BaseModel
{
    protected $table = 'handmade_order_quotes';

    protected $fillable = [
        'order_id',
        'product_cost',
        'shipping_cost',
        'fulfill_fee',
        'packing_fee',
        'deposit_percent',
        'expected_delivery_date',
        'note',
        'quoted_by',
        'quoted_at',
        'accepted_at',
        'deposit_paid_at',
        'final_paid_at',
    ];

    protected $casts = [
        'product_cost' => 'float',
        'shipping_cost' => 'float',
        'fulfill_fee' => 'float',
        'packing_fee' => 'float',
        'deposit_percent' => 'integer',
        'expected_delivery_date' => 'date',
        'quoted_at' => 'datetime',
        'accepted_at' => 'datetime',
        'deposit_paid_at' => 'datetime',
        'final_paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    protected function total(): Attribute
    {
        return Attribute::get(fn (): float => round(
            $this->product_cost + $this->shipping_cost + $this->fulfill_fee + $this->packing_fee,
            2
        ));
    }

    /**
     * Milestone 1 — paid when the customer accepts the quote:
     * 50% of the product cost + the full shipping cost.
     */
    protected function depositAmount(): Attribute
    {
        return Attribute::get(fn (): float => round($this->product_cost * $this->depositRate(), 2) + $this->shipping_cost);
    }

    /**
     * Milestone 2 — paid when the customer approves the finished item.
     * Derived from the total so the two milestones always add up exactly,
     * even when half the product cost does not divide evenly.
     */
    protected function finalAmount(): Attribute
    {
        return Attribute::get(fn (): float => round($this->total - $this->deposit_amount, 2));
    }

    /** Share of the product cost taken as deposit; 50% unless the quote says otherwise. */
    public function depositRate(): float
    {
        return ($this->deposit_percent ?: 50) / 100;
    }

    /** What the customer has actually been charged so far. */
    protected function paidAmount(): Attribute
    {
        return Attribute::get(fn (): float => round(
            ($this->isDepositPaid() ? $this->deposit_amount : 0)
            + ($this->isFinalPaid() ? $this->final_amount : 0),
            2
        ));
    }

    /** What is still owed. Derived from the total so the two always add back up. */
    protected function outstandingAmount(): Attribute
    {
        return Attribute::get(fn (): float => round($this->total - $this->paid_amount, 2));
    }

    /**
     * The two payments in order, each with whether and when it was taken. Both the
     * order page and the order list read this, so they can never disagree.
     *
     * @return array<int, array{key: string, amount: float, paid: bool, paid_at: ?\Illuminate\Support\Carbon}>
     */
    public function milestones(): array
    {
        return [
            [
                'key' => 'deposit',
                'amount' => $this->deposit_amount,
                'paid' => $this->isDepositPaid(),
                'paid_at' => $this->deposit_paid_at,
            ],
            [
                'key' => 'final',
                'amount' => $this->final_amount,
                'paid' => $this->isFinalPaid(),
                'paid_at' => $this->final_paid_at,
            ],
        ];
    }

    public function isQuoted(): bool
    {
        return (bool) $this->quoted_at;
    }

    public function isAccepted(): bool
    {
        return (bool) $this->accepted_at;
    }

    public function isDepositPaid(): bool
    {
        return (bool) $this->deposit_paid_at;
    }

    public function isFinalPaid(): bool
    {
        return (bool) $this->final_paid_at;
    }
}
