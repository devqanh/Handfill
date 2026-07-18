<?php

namespace Botble\EWallet\Services;

use Botble\Ecommerce\Models\Order;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Models\WalletTransaction;

class WalletPaymentService
{
    public function __construct(
        protected WalletService $walletService
    ) {
    }

    public function processOrderPayment(Order $order, int $amountCents): WalletTransaction
    {
        $idempotencyKey = 'order_payment_' . $order->id;

        $existing = $this->walletService->findTransactionByIdempotencyKey($idempotencyKey);
        if ($existing) {
            return $existing;
        }

        return $this->walletService->debit(
            customerId: $order->user_id,
            amountCents: $amountCents,
            type: TransactionTypeEnum::PAYMENT,
            referenceType: Order::class,
            referenceId: $order->id,
            description: trans('plugins/e-wallet::e-wallet.transaction.order_payment', [
                'code' => $order->code,
            ]),
            idempotencyKey: $idempotencyKey,
            metadata: [
                'order_code' => $order->code,
                'order_amount' => $order->amount,
            ]
        );
    }

    public function canPayWithWallet(int|string $customerId, int $amountCents): bool
    {
        $balance = $this->walletService->getBalance($customerId);

        return $balance >= $amountCents;
    }

    public function getMaxPayableAmount(int|string $customerId): int
    {
        return $this->walletService->getBalance($customerId);
    }
}
