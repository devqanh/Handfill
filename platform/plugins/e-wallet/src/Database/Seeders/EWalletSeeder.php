<?php

namespace Botble\EWallet\Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Order;
use Botble\EWallet\Enums\TopUpStatusEnum;
use Botble\EWallet\Enums\TransactionStatusEnum;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Models\Wallet;
use Botble\EWallet\Models\WalletTopUp;
use Botble\EWallet\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Support\Str;

class EWalletSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->truncateTables();

        $customers = Customer::query()->limit(15)->get();

        if ($customers->isEmpty()) {
            $this->command->warn('No customers found. Please seed customers first.');

            return;
        }

        $orders = Order::query()
            ->whereIn('user_id', $customers->pluck('id'))
            ->get();

        $currencyCode = strtoupper(cms_currency()->getApplicationCurrency()->title ?? 'USD');

        foreach ($customers as $customer) {
            $wallet = $this->createWallet($customer, $currencyCode);
            $customerOrders = $orders->where('user_id', $customer->id);

            if ($customer->email === 'customer@botble.com') {
                $this->createDemoCustomerTransactions($wallet, $customer);
            } else {
                $this->createTransactions($wallet, $customer, $customerOrders);
            }

            $this->createTopUps($wallet, $customer);
        }

        $this->command->info('E-Wallet seeder completed successfully.');
        $this->command->info('Created ' . Wallet::query()->count() . ' wallets.');
        $this->command->info('Created ' . WalletTransaction::query()->count() . ' transactions.');
        $this->command->info('Created ' . WalletTopUp::query()->count() . ' top-ups.');
    }

    protected function truncateTables(): void
    {
        WalletTransaction::query()->truncate();
        WalletTopUp::query()->truncate();
        Wallet::query()->truncate();
    }

    protected function createWallet(Customer $customer, string $currencyCode): Wallet
    {
        return Wallet::query()->create([
            'customer_id' => $customer->id,
            'balance' => 0,
            'currency_code' => $currencyCode,
        ]);
    }

    protected function createTransactions(Wallet $wallet, Customer $customer, $orders): void
    {
        $transactionCount = rand(5, 15);
        $currentBalance = 0;

        $typeWeights = [
            TransactionTypeEnum::TOP_UP => 35,
            TransactionTypeEnum::PAYMENT => 30,
            TransactionTypeEnum::REFUND => 20,
            TransactionTypeEnum::ADMIN_ADJUSTMENT => 10,
            TransactionTypeEnum::VENDOR_PAYOUT => 5,
        ];

        $transactions = [];

        for ($i = 0; $i < $transactionCount; $i++) {
            $createdAt = Carbon::now()
                ->subDays(rand(1, 90))
                ->subHours(rand(0, 23))
                ->subMinutes(rand(0, 59));

            $type = $this->getWeightedRandomType($typeWeights);
            $amount = $this->generateAmount($type, $currentBalance);

            if ($amount === 0) {
                continue;
            }

            $balanceBefore = $currentBalance;
            $currentBalance += $amount;

            $transactions[] = [
                'wallet_id' => $wallet->id,
                'customer_id' => $customer->id,
                'type' => $type,
                'status' => TransactionStatusEnum::COMPLETED,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $currentBalance,
                'reference_type' => $this->getReferenceType($type, $orders),
                'reference_id' => $this->getReferenceId($type, $orders),
                'description' => $this->generateDescription($type, $amount, $orders),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }

        usort($transactions, fn ($a, $b) => $a['created_at'] <=> $b['created_at']);

        $runningBalance = 0;
        foreach ($transactions as &$tx) {
            $tx['balance_before'] = $runningBalance;
            $runningBalance += $tx['amount'];
            $tx['balance_after'] = $runningBalance;
            $tx['idempotency_key'] = Str::uuid()->toString();
        }
        unset($tx);

        foreach ($transactions as $transaction) {
            WalletTransaction::query()->create($transaction);
        }

        $wallet->update(['balance' => $runningBalance]);
    }

    protected function createDemoCustomerTransactions(Wallet $wallet, Customer $customer): void
    {
        $transactions = [
            [
                'type' => TransactionTypeEnum::TOP_UP,
                'amount' => 50000 * 100,
                'days_ago' => 30,
                'description' => trans('plugins/e-wallet::e-wallet.transaction.descriptions.topup', [
                    'amount' => format_price(50000),
                ]),
            ],
            [
                'type' => TransactionTypeEnum::PAYMENT,
                'amount' => -15000 * 100,
                'days_ago' => 25,
                'description' => trans('plugins/e-wallet::e-wallet.transaction.descriptions.payment'),
            ],
            [
                'type' => TransactionTypeEnum::TOP_UP,
                'amount' => 25000 * 100,
                'days_ago' => 20,
                'description' => trans('plugins/e-wallet::e-wallet.transaction.descriptions.topup', [
                    'amount' => format_price(25000),
                ]),
            ],
            [
                'type' => TransactionTypeEnum::REFUND,
                'amount' => 5000 * 100,
                'days_ago' => 15,
                'description' => trans('plugins/e-wallet::e-wallet.transaction.descriptions.refund'),
            ],
            [
                'type' => TransactionTypeEnum::PAYMENT,
                'amount' => -10000 * 100,
                'days_ago' => 10,
                'description' => trans('plugins/e-wallet::e-wallet.transaction.descriptions.payment'),
            ],
            [
                'type' => TransactionTypeEnum::ADMIN_ADJUSTMENT,
                'amount' => 2500 * 100,
                'days_ago' => 5,
                'description' => trans('plugins/e-wallet::e-wallet.transaction.descriptions.admin_credit'),
            ],
        ];

        $runningBalance = 0;

        foreach ($transactions as $tx) {
            $createdAt = Carbon::now()->subDays($tx['days_ago']);

            WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'customer_id' => $customer->id,
                'type' => $tx['type'],
                'status' => TransactionStatusEnum::COMPLETED,
                'amount' => $tx['amount'],
                'balance_before' => $runningBalance,
                'balance_after' => $runningBalance + $tx['amount'],
                'description' => $tx['description'],
                'idempotency_key' => Str::uuid()->toString(),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $runningBalance += $tx['amount'];
        }

        $wallet->update(['balance' => $runningBalance]);
    }

    protected function createTopUps(Wallet $wallet, Customer $customer): void
    {
        $topUpCount = rand(1, 4);

        for ($i = 0; $i < $topUpCount; $i++) {
            $amount = $this->generateTopUpAmount();
            $createdAt = Carbon::now()
                ->subDays(rand(1, 60))
                ->subHours(rand(0, 23));

            $status = $this->getRandomTopUpStatus();

            WalletTopUp::query()->create([
                'customer_id' => $customer->id,
                'wallet_id' => $wallet->id,
                'code' => 'TOP-' . strtoupper(Str::random(8)),
                'amount' => $amount,
                'converted_amount' => $amount,
                'currency_code' => $wallet->currency_code,
                'wallet_currency_code' => $wallet->currency_code,
                'exchange_rate' => 1.0,
                'status' => $status,
                'payment_method' => $this->getRandomPaymentMethod(),
                'payment_id' => $status === TopUpStatusEnum::COMPLETED ? 'pay_' . Str::random(24) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    protected function generateAmount(string $type, int $currentBalance): int
    {
        return match ($type) {
            TransactionTypeEnum::TOP_UP => $this->generateTopUpAmount(),
            TransactionTypeEnum::PAYMENT => $this->generatePaymentAmount($currentBalance),
            TransactionTypeEnum::REFUND => rand(500, 5000) * 100,
            TransactionTypeEnum::ADMIN_ADJUSTMENT => $this->generateAdjustmentAmount(),
            TransactionTypeEnum::VENDOR_PAYOUT => $this->generatePayoutAmount($currentBalance),
            default => 0,
        };
    }

    protected function generateTopUpAmount(): int
    {
        $amounts = [1000, 2500, 5000, 10000, 25000, 50000];

        return $amounts[array_rand($amounts)] * 100;
    }

    protected function generatePaymentAmount(int $currentBalance): int
    {
        if ($currentBalance <= 0) {
            return 0;
        }

        $maxPayment = min($currentBalance, 50000 * 100);
        $payment = rand(1000, (int) ($maxPayment / 100)) * 100;

        return -$payment;
    }

    protected function generateAdjustmentAmount(): int
    {
        $isPositive = rand(0, 100) > 30;
        $amount = rand(100, 5000) * 100;

        return $isPositive ? $amount : -$amount;
    }

    protected function generatePayoutAmount(int $currentBalance): int
    {
        if ($currentBalance <= 10000 * 100) {
            return 0;
        }

        return -rand(5000, 20000) * 100;
    }

    protected function getReferenceType(string $type, $orders): ?string
    {
        if (in_array($type, [TransactionTypeEnum::PAYMENT, TransactionTypeEnum::REFUND]) && $orders->isNotEmpty()) {
            return Order::class;
        }

        return null;
    }

    protected function getReferenceId(string $type, $orders): ?int
    {
        if (in_array($type, [TransactionTypeEnum::PAYMENT, TransactionTypeEnum::REFUND]) && $orders->isNotEmpty()) {
            return $orders->random()->id;
        }

        return null;
    }

    protected function generateDescription(string $type, int $amount, $orders): string
    {
        $order = $orders->isNotEmpty() ? $orders->random() : null;

        return match ($type) {
            TransactionTypeEnum::TOP_UP => trans('plugins/e-wallet::e-wallet.transaction.descriptions.topup', [
                'amount' => format_price(abs($amount) / 100),
            ]),
            TransactionTypeEnum::PAYMENT => $order
                ? trans('plugins/e-wallet::e-wallet.transaction.descriptions.payment_order', ['code' => $order->code])
                : trans('plugins/e-wallet::e-wallet.transaction.descriptions.payment'),
            TransactionTypeEnum::REFUND => $order
                ? trans('plugins/e-wallet::e-wallet.transaction.descriptions.refund_order', ['code' => $order->code])
                : trans('plugins/e-wallet::e-wallet.transaction.descriptions.refund'),
            TransactionTypeEnum::ADMIN_ADJUSTMENT => $amount > 0
                ? trans('plugins/e-wallet::e-wallet.transaction.descriptions.admin_credit')
                : trans('plugins/e-wallet::e-wallet.transaction.descriptions.admin_debit'),
            TransactionTypeEnum::VENDOR_PAYOUT => trans('plugins/e-wallet::e-wallet.transaction.descriptions.vendor_payout'),
            default => '',
        };
    }

    protected function getWeightedRandomType(array $weights): string
    {
        $random = rand(1, 100);
        $sum = 0;

        foreach ($weights as $type => $weight) {
            $sum += $weight;
            if ($random <= $sum) {
                return $type;
            }
        }

        return TransactionTypeEnum::TOP_UP;
    }

    protected function getRandomTopUpStatus(): string
    {
        $statuses = [
            ['status' => TopUpStatusEnum::COMPLETED, 'weight' => 70],
            ['status' => TopUpStatusEnum::PENDING, 'weight' => 15],
            ['status' => TopUpStatusEnum::FAILED, 'weight' => 10],
            ['status' => TopUpStatusEnum::CANCELLED, 'weight' => 5],
        ];

        $random = rand(1, 100);
        $sum = 0;

        foreach ($statuses as $item) {
            $sum += $item['weight'];
            if ($random <= $sum) {
                return $item['status'];
            }
        }

        return TopUpStatusEnum::COMPLETED;
    }

    protected function getRandomPaymentMethod(): string
    {
        $methods = ['stripe', 'paypal', 'bank_transfer', 'cod'];

        return $methods[array_rand($methods)];
    }
}
