<?php

namespace Botble\EWallet\Listeners;

use Botble\Ecommerce\Events\OrderReturnedEvent;
use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\OrderReturn;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Helpers\WalletHelper;
use Botble\EWallet\Models\WalletTransaction;
use Botble\EWallet\Services\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessOrderRefund implements ShouldQueue
{
    public function __construct(
        protected WalletService $walletService,
        protected WalletHelper $helper
    ) {
    }

    public function handle(OrderReturnedEvent $event): void
    {
        if (! $this->helper->isEnabled()) {
            return;
        }

        if (get_wallet_setting('refund_to_wallet', 'wallet') !== 'wallet') {
            return;
        }

        $orderReturn = $event->order;
        $order = $orderReturn->order;

        if (! $order->user_id) {
            return;
        }

        $refundAmount = $this->calculateRefundAmount($orderReturn);

        if ($refundAmount <= 0) {
            return;
        }

        $this->creditRefundToWallet($order, $orderReturn, $refundAmount);
    }

    protected function calculateRefundAmount(OrderReturn $orderReturn): float
    {
        $total = 0;

        foreach ($orderReturn->items as $item) {
            $total += $item->price * $item->qty;
        }

        return $total;
    }

    protected function creditRefundToWallet(Order $order, OrderReturn $orderReturn, float $refundAmount): void
    {
        $amountCents = (int) round($refundAmount * 100);

        $existingRefunds = WalletTransaction::query()
            ->where('reference_type', OrderReturn::class)
            ->where('reference_id', $orderReturn->id)
            ->where('type', TransactionTypeEnum::REFUND)
            ->sum('amount');

        $maxRefundable = (int) round($order->amount * 100) - abs($existingRefunds);
        $amountCents = min($amountCents, $maxRefundable);

        if ($amountCents <= 0) {
            return;
        }

        $idempotencyKey = 'order_return_' . $orderReturn->id;

        if ($this->walletService->findTransactionByIdempotencyKey($idempotencyKey)) {
            return;
        }

        $this->walletService->credit(
            customerId: $order->user_id,
            amountCents: $amountCents,
            type: TransactionTypeEnum::REFUND,
            referenceType: OrderReturn::class,
            referenceId: $orderReturn->id,
            description: trans('plugins/e-wallet::e-wallet.transaction.order_refund', [
                'code' => $order->code,
            ]),
            idempotencyKey: $idempotencyKey,
            metadata: [
                'order_code' => $order->code,
                'order_id' => $order->id,
                'return_code' => $orderReturn->code,
                'original_amount' => $order->amount,
                'refund_amount' => $refundAmount,
            ]
        );
    }
}
