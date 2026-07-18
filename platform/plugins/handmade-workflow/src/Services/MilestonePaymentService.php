<?php

namespace Botble\HandmadeWorkflow\Services;

use Botble\Ecommerce\Models\Order;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Exceptions\InsufficientBalanceException;
use Botble\EWallet\Services\WalletService;
use Botble\HandmadeWorkflow\Models\OrderQuote;
use RuntimeException;

/**
 * Charges the two payment milestones against the customer's wallet.
 *
 * The wallet has no escrow, so "deposit" means the money really leaves the wallet
 * at milestone 1. Both charges are idempotent per order, so a double click or a
 * retried request can never debit twice.
 */
class MilestonePaymentService
{
    public const MILESTONE_DEPOSIT = 'deposit';

    public const MILESTONE_FINAL = 'final';

    public function __construct(protected WalletService $wallet)
    {
    }

    public function amountFor(OrderQuote $quote, string $milestone): float
    {
        return $milestone === self::MILESTONE_DEPOSIT
            ? $quote->deposit_amount
            : $quote->final_amount;
    }

    public function balanceOf(Order $order): float
    {
        if (! $order->user_id) {
            return 0;
        }

        return $this->wallet->getBalance($order->user_id) / 100;
    }

    public function hasEnoughBalance(Order $order, OrderQuote $quote, string $milestone): bool
    {
        return $this->balanceOf($order) >= $this->amountFor($quote, $milestone);
    }

    /**
     * How much the customer still needs to top up to clear this milestone.
     */
    public function shortfall(Order $order, OrderQuote $quote, string $milestone): float
    {
        return max(0, round($this->amountFor($quote, $milestone) - $this->balanceOf($order), 2));
    }

    /**
     * @throws InsufficientBalanceException when the wallet cannot cover the milestone.
     * @throws RuntimeException when the order has no customer.
     */
    public function charge(Order $order, OrderQuote $quote, string $milestone): void
    {
        if ($this->isPaid($quote, $milestone)) {
            return;
        }

        if (! $order->user_id) {
            throw new RuntimeException('Order has no customer to charge.');
        }

        $amount = $this->amountFor($quote, $milestone);

        if ($amount > 0) {
            // A customer who has never topped up has no wallet row yet; without this the
            // debit would blow up with ModelNotFoundException instead of a clean
            // "insufficient balance" that tells them to top up.
            $this->wallet->getOrCreateWallet($order->user_id);

            $this->wallet->debit(
                customerId: $order->user_id,
                amountCents: (int) round($amount * 100),
                type: TransactionTypeEnum::PAYMENT,
                referenceType: Order::class,
                referenceId: $order->getKey(),
                description: trans(
                    "plugins/handmade-workflow::handmade-workflow.payment.{$milestone}_description",
                    ['code' => $order->code]
                ),
                // Per order + per milestone: retries and double submits cannot debit twice.
                idempotencyKey: sprintf('handmade_order_%d_%s', $order->getKey(), $milestone),
                metadata: [
                    'handmade_milestone' => $milestone,
                    'order_code' => $order->code,
                ],
            );
        }

        $quote->forceFill([
            $milestone === self::MILESTONE_DEPOSIT ? 'deposit_paid_at' : 'final_paid_at' => now(),
        ])->save();
    }

    public function isPaid(OrderQuote $quote, string $milestone): bool
    {
        return $milestone === self::MILESTONE_DEPOSIT
            ? $quote->isDepositPaid()
            : $quote->isFinalPaid();
    }
}
