<?php

namespace Botble\EWallet\Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\EWallet\Enums\PayoutPaymentMethodsEnum;
use Botble\EWallet\Enums\WithdrawalStatusEnum;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\Withdrawal;
use Carbon\Carbon;

class WithdrawalSeeder extends BaseSeeder
{
    public function run(): void
    {
        Withdrawal::query()->truncate();

        $wallets = Wallet::query()
            ->where('balance', '>', 0)
            ->with('customer')
            ->limit(10)
            ->get();

        if ($wallets->isEmpty()) {
            $this->command->warn('No wallets with balance found. Please run EWalletSeeder first.');

            return;
        }

        foreach ($wallets as $wallet) {
            $this->createWithdrawals($wallet);
        }

        $this->command->info('Withdrawal seeder completed successfully.');
        $this->command->info('Created ' . Withdrawal::query()->count() . ' withdrawals.');
    }

    protected function createWithdrawals(Wallet $wallet): void
    {
        $withdrawalCount = rand(1, 3);

        for ($i = 0; $i < $withdrawalCount; $i++) {
            $status = $this->getRandomStatus();
            $paymentChannel = $this->getRandomPaymentChannel();
            $amount = $this->generateWithdrawalAmount();

            $createdAt = Carbon::now()
                ->subDays(rand(1, 60))
                ->subHours(rand(0, 23))
                ->subMinutes(rand(0, 59));

            $processedAt = null;
            $processedBy = null;

            if (in_array($status, [WithdrawalStatusEnum::COMPLETED, WithdrawalStatusEnum::REJECTED])) {
                $processedAt = $createdAt->copy()->addHours(rand(1, 48));
                $processedBy = 1;
            }

            Withdrawal::query()->create([
                'wallet_id' => $wallet->id,
                'customer_id' => $wallet->customer_id,
                'amount' => $amount,
                'currency_code' => $wallet->currency_code,
                'status' => $status,
                'payment_channel' => $paymentChannel,
                'payment_details' => $this->generatePaymentDetails($paymentChannel),
                'bank_info' => $this->generateBankInfo($paymentChannel),
                'notes' => $this->generateNotes($status),
                'processed_by' => $processedBy,
                'processed_at' => $processedAt,
                'created_at' => $createdAt,
                'updated_at' => $processedAt ?? $createdAt,
            ]);
        }
    }

    protected function generateWithdrawalAmount(): int
    {
        $amounts = [5000, 10000, 15000, 20000, 25000, 50000];

        return $amounts[array_rand($amounts)] * 100;
    }

    protected function getRandomStatus(): string
    {
        $statuses = [
            ['status' => WithdrawalStatusEnum::PENDING, 'weight' => 30],
            ['status' => WithdrawalStatusEnum::PROCESSING, 'weight' => 15],
            ['status' => WithdrawalStatusEnum::COMPLETED, 'weight' => 40],
            ['status' => WithdrawalStatusEnum::REJECTED, 'weight' => 10],
            ['status' => WithdrawalStatusEnum::CANCELLED, 'weight' => 5],
        ];

        $random = rand(1, 100);
        $sum = 0;

        foreach ($statuses as $item) {
            $sum += $item['weight'];
            if ($random <= $sum) {
                return $item['status'];
            }
        }

        return WithdrawalStatusEnum::PENDING;
    }

    protected function getRandomPaymentChannel(): string
    {
        $channels = [
            PayoutPaymentMethodsEnum::BANK_TRANSFER,
            PayoutPaymentMethodsEnum::PAYPAL,
            PayoutPaymentMethodsEnum::OTHER,
        ];

        return $channels[array_rand($channels)];
    }

    protected function generatePaymentDetails(string $paymentChannel): ?string
    {
        if ($paymentChannel === PayoutPaymentMethodsEnum::PAYPAL) {
            return 'customer' . rand(100, 999) . '@example.com';
        }

        if ($paymentChannel === PayoutPaymentMethodsEnum::OTHER) {
            return 'Payment via mobile wallet or other method';
        }

        return null;
    }

    protected function generateBankInfo(string $paymentChannel): ?array
    {
        if ($paymentChannel !== PayoutPaymentMethodsEnum::BANK_TRANSFER) {
            return null;
        }

        $banks = ['Chase Bank', 'Bank of America', 'Wells Fargo', 'Citibank', 'US Bank'];

        return [
            'bank_name' => $banks[array_rand($banks)],
            'account_number' => '**** **** ' . rand(1000, 9999),
            'account_holder' => 'Account Holder ' . rand(1, 100),
            'routing_number' => rand(100000000, 999999999),
        ];
    }

    protected function generateNotes(string $status): ?string
    {
        if ($status === WithdrawalStatusEnum::REJECTED) {
            $reasons = [
                'Insufficient documentation provided',
                'Bank account verification failed',
                'Suspicious activity detected',
                'Account information mismatch',
            ];

            return $reasons[array_rand($reasons)];
        }

        if ($status === WithdrawalStatusEnum::COMPLETED) {
            return 'Processed successfully. Transaction ID: TXN' . rand(100000, 999999);
        }

        return null;
    }
}
