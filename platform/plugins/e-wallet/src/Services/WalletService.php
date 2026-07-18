<?php

namespace Botble\EWallet\Services;

use Botble\EWallet\Enums\TransactionStatusEnum;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Events\WalletCredited;
use Botble\EWallet\Events\WalletDebited;
use Botble\EWallet\Events\WalletTransactionCreated;
use Botble\EWallet\Exceptions\DuplicateTransactionException;
use Botble\EWallet\Exceptions\InsufficientBalanceException;
use Botble\EWallet\Helpers\WalletHelper;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(
        protected WalletHelper $helper
    ) {
    }

    public function getOrCreateWallet(int|string $customerId): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['customer_id' => $customerId],
            [
                'balance' => 0,
                'currency_code' => $this->helper->getDefaultCurrency(),
            ]
        );
    }

    public function getBalance(int|string $customerId): int
    {
        $wallet = Wallet::query()
            ->where('customer_id', $customerId)
            ->first();

        return $wallet?->balance ?? 0;
    }

    public function credit(
        int|string $customerId,
        int $amountCents,
        string $type,
        ?string $referenceType = null,
        int|string|null $referenceId = null,
        ?string $description = null,
        ?string $idempotencyKey = null,
        ?array $metadata = null
    ): WalletTransaction {
        if ($idempotencyKey && $this->transactionExists($idempotencyKey)) {
            throw new DuplicateTransactionException($idempotencyKey);
        }

        return DB::transaction(function () use (
            $customerId,
            $amountCents,
            $type,
            $referenceType,
            $referenceId,
            $description,
            $idempotencyKey,
            $metadata
        ) {
            $wallet = Wallet::query()
                ->lockForUpdate()
                ->where('customer_id', $customerId)
                ->first();

            if (! $wallet) {
                $wallet = $this->getOrCreateWallet($customerId);
                $wallet = Wallet::query()->lockForUpdate()->find($wallet->id);
            }

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $amountCents;

            $wallet->balance = $balanceAfter;
            $wallet->save();

            $transaction = WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'customer_id' => $customerId,
                'currency_code' => $wallet->currency_code,
                'type' => $type,
                'status' => TransactionStatusEnum::COMPLETED,
                'amount' => $amountCents,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
                'metadata' => $metadata,
            ]);

            event(new WalletCredited($wallet, $transaction));
            event(new WalletTransactionCreated($transaction));

            return $transaction;
        });
    }

    public function debit(
        int|string $customerId,
        int $amountCents,
        string $type,
        ?string $referenceType = null,
        int|string|null $referenceId = null,
        ?string $description = null,
        ?string $idempotencyKey = null,
        ?array $metadata = null
    ): WalletTransaction {
        if ($idempotencyKey && $this->transactionExists($idempotencyKey)) {
            throw new DuplicateTransactionException($idempotencyKey);
        }

        return DB::transaction(function () use (
            $customerId,
            $amountCents,
            $type,
            $referenceType,
            $referenceId,
            $description,
            $idempotencyKey,
            $metadata
        ) {
            $wallet = Wallet::query()
                ->lockForUpdate()
                ->where('customer_id', $customerId)
                ->firstOrFail();

            if (! $wallet->hasSufficientBalance($amountCents)) {
                throw new InsufficientBalanceException($amountCents, $wallet->balance);
            }

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore - $amountCents;

            $wallet->balance = $balanceAfter;
            $wallet->save();

            $transaction = WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'customer_id' => $customerId,
                'currency_code' => $wallet->currency_code,
                'type' => $type,
                'status' => TransactionStatusEnum::COMPLETED,
                'amount' => -$amountCents,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
                'metadata' => $metadata,
            ]);

            event(new WalletDebited($wallet, $transaction));
            event(new WalletTransactionCreated($transaction));

            return $transaction;
        });
    }

    public function adjustBalance(
        int|string $customerId,
        int $amountCents,
        ?string $description = null,
        ?int $createdBy = null,
        ?array $metadata = null
    ): WalletTransaction {
        $metadata = array_merge($metadata ?? [], [
            'created_by' => $createdBy ?? auth()->id(),
        ]);

        if ($amountCents > 0) {
            return $this->credit(
                $customerId,
                $amountCents,
                TransactionTypeEnum::ADMIN_ADJUSTMENT,
                null,
                null,
                $description ?? trans('plugins/e-wallet::e-wallet.transaction.admin_credit'),
                null,
                $metadata
            );
        }

        return $this->debit(
            $customerId,
            abs($amountCents),
            TransactionTypeEnum::ADMIN_ADJUSTMENT,
            null,
            null,
            $description ?? trans('plugins/e-wallet::e-wallet.transaction.admin_debit'),
            null,
            $metadata
        );
    }

    protected function transactionExists(string $idempotencyKey): bool
    {
        return WalletTransaction::query()
            ->where('idempotency_key', $idempotencyKey)
            ->exists();
    }

    public function findTransactionByIdempotencyKey(string $key): ?WalletTransaction
    {
        return WalletTransaction::query()
            ->where('idempotency_key', $key)
            ->first();
    }

    public function getTransactionsByOrder($order): Collection
    {
        return WalletTransaction::query()
            ->where('reference_type', $order::class)
            ->where('reference_id', $order->id)
            ->latest()
            ->get();
    }
}
