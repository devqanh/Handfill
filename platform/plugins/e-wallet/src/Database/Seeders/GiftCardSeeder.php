<?php

namespace Botble\EWallet\Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\Ecommerce\Models\Customer;
use Botble\EWallet\Enums\GiftCardStatusEnum;
use Botble\EWallet\Models\GiftCard;

class GiftCardSeeder extends BaseSeeder
{
    public function run(): void
    {
        GiftCard::query()->truncate();

        $currencyCode = strtoupper(cms_currency()->getApplicationCurrency()->title ?? 'USD');

        $demoCustomer = Customer::query()->where('email', 'customer@botble.com')->first();
        $otherCustomers = Customer::query()
            ->where('email', '!=', 'customer@botble.com')
            ->limit(3)
            ->get();

        $giftCards = [
            [
                'code' => 'TEST-1000-0001',
                'initial_value' => 10 * 100,
                'balance' => 10 * 100,
                'status' => GiftCardStatusEnum::ACTIVE,
                'note' => 'Test gift card - $10',
            ],
            [
                'code' => 'TEST-2500-0002',
                'initial_value' => 25 * 100,
                'balance' => 25 * 100,
                'status' => GiftCardStatusEnum::ACTIVE,
                'note' => 'Test gift card - $25',
            ],
            [
                'code' => 'TEST-5000-0003',
                'initial_value' => 50 * 100,
                'balance' => 50 * 100,
                'status' => GiftCardStatusEnum::ACTIVE,
                'note' => 'Test gift card - $50',
            ],
            [
                'code' => 'TEST-10000-0004',
                'initial_value' => 100 * 100,
                'balance' => 100 * 100,
                'status' => GiftCardStatusEnum::ACTIVE,
                'note' => 'Test gift card - $100',
            ],
            [
                'code' => 'TEST-PARTIAL-0005',
                'initial_value' => 50 * 100,
                'balance' => 25 * 100,
                'status' => GiftCardStatusEnum::ACTIVE,
                'note' => 'Test gift card - partially used ($25 remaining)',
            ],
            [
                'code' => 'TEST-USED-0006',
                'initial_value' => 25 * 100,
                'balance' => 0,
                'status' => GiftCardStatusEnum::REDEEMED,
                'redeemed_at' => now()->subDays(5),
                'note' => 'Test gift card - already redeemed',
            ],
            [
                'code' => 'TEST-EXPIRED-0007',
                'initial_value' => 50 * 100,
                'balance' => 50 * 100,
                'status' => GiftCardStatusEnum::EXPIRED,
                'expires_at' => now()->subDays(30),
                'note' => 'Test gift card - expired',
            ],
            [
                'code' => 'TEST-CANCELLED-0008',
                'initial_value' => 25 * 100,
                'balance' => 25 * 100,
                'status' => GiftCardStatusEnum::CANCELLED,
                'note' => 'Test gift card - cancelled',
            ],
        ];

        foreach ($giftCards as $data) {
            $isActive = $data['status'] === GiftCardStatusEnum::ACTIVE;

            GiftCard::query()->create(array_merge($data, [
                'currency_code' => $currencyCode,
                'activated_at' => $isActive ? now() : null,
                'issued_by' => 1,
            ]));
        }

        if ($demoCustomer) {
            $this->createCustomerGiftCards($demoCustomer, $otherCustomers, $currencyCode);
        }

        $this->command->info('Gift card seeder completed!');
        $this->command->newLine();
        $this->command->info('Test gift cards created:');
        $this->command->table(
            ['Code', 'Value', 'Status'],
            collect($giftCards)->map(fn ($card) => [
                $card['code'],
                '$' . number_format($card['initial_value'] / 100, 2),
                $card['status'],
            ])->toArray()
        );

        if ($demoCustomer) {
            $this->command->newLine();
            $this->command->info("Demo customer ({$demoCustomer->email}) gift cards also created!");
        }
    }

    protected function createCustomerGiftCards(Customer $demoCustomer, $otherCustomers, string $currencyCode): void
    {
        $customerGiftCards = [
            [
                'code' => 'MYCARD-ACTIVE-001',
                'initial_value' => 50 * 100,
                'balance' => 50 * 100,
                'status' => GiftCardStatusEnum::ACTIVE,
                'purchased_by_customer_id' => $demoCustomer->id,
                'recipient_name' => 'John Smith',
                'recipient_email' => 'john@example.com',
                'gift_message' => 'Happy Birthday! Enjoy your shopping!',
                'note' => 'Purchased by demo customer - sent to friend',
            ],
            [
                'code' => 'MYCARD-ACTIVE-002',
                'initial_value' => 100 * 100,
                'balance' => 100 * 100,
                'status' => GiftCardStatusEnum::ACTIVE,
                'purchased_by_customer_id' => $demoCustomer->id,
                'recipient_name' => 'Jane Doe',
                'recipient_email' => 'jane@example.com',
                'gift_message' => 'Congratulations on your promotion!',
                'note' => 'Purchased by demo customer - sent to colleague',
            ],
            [
                'code' => 'MYCARD-PARTIAL-003',
                'initial_value' => 75 * 100,
                'balance' => 30 * 100,
                'status' => GiftCardStatusEnum::ACTIVE,
                'purchased_by_customer_id' => $demoCustomer->id,
                'recipient_name' => 'Mike Wilson',
                'recipient_email' => 'mike@example.com',
                'gift_message' => 'Thanks for being a great friend!',
                'note' => 'Purchased by demo customer - partially used by recipient',
            ],
            [
                'code' => 'MYCARD-REDEEMED-004',
                'initial_value' => 25 * 100,
                'balance' => 0,
                'status' => GiftCardStatusEnum::REDEEMED,
                'purchased_by_customer_id' => $demoCustomer->id,
                'recipient_name' => 'Sarah Johnson',
                'recipient_email' => 'sarah@example.com',
                'gift_message' => 'Enjoy!',
                'redeemed_at' => now()->subDays(10),
                'redeemed_by_customer_id' => $otherCustomers->first()?->id,
                'note' => 'Purchased by demo customer - fully redeemed',
            ],
            [
                'code' => 'MYCARD-SELF-005',
                'initial_value' => 200 * 100,
                'balance' => 200 * 100,
                'status' => GiftCardStatusEnum::ACTIVE,
                'purchased_by_customer_id' => $demoCustomer->id,
                'note' => 'Purchased by demo customer - for self use',
            ],
            [
                'code' => 'RECEIVED-GIFT-001',
                'initial_value' => 50 * 100,
                'balance' => 50 * 100,
                'status' => GiftCardStatusEnum::ACTIVE,
                'purchased_by_customer_id' => $otherCustomers->get(0)?->id,
                'customer_id' => $demoCustomer->id,
                'recipient_name' => $demoCustomer->name,
                'recipient_email' => $demoCustomer->email,
                'gift_message' => 'Happy holidays! From your friend.',
                'note' => 'Received by demo customer from another customer',
            ],
            [
                'code' => 'RECEIVED-GIFT-002',
                'initial_value' => 75 * 100,
                'balance' => 75 * 100,
                'status' => GiftCardStatusEnum::ACTIVE,
                'purchased_by_customer_id' => $otherCustomers->get(1)?->id,
                'customer_id' => $demoCustomer->id,
                'recipient_name' => $demoCustomer->name,
                'recipient_email' => $demoCustomer->email,
                'gift_message' => 'Thank you for everything!',
                'note' => 'Received by demo customer - birthday gift',
            ],
            [
                'code' => 'REDEEMED-BY-ME-001',
                'initial_value' => 30 * 100,
                'balance' => 0,
                'status' => GiftCardStatusEnum::REDEEMED,
                'redeemed_by_customer_id' => $demoCustomer->id,
                'redeemed_at' => now()->subDays(15),
                'note' => 'Redeemed by demo customer to wallet',
            ],
            [
                'code' => 'REDEEMED-BY-ME-002',
                'initial_value' => 100 * 100,
                'balance' => 0,
                'status' => GiftCardStatusEnum::REDEEMED,
                'customer_id' => $demoCustomer->id,
                'redeemed_by_customer_id' => $demoCustomer->id,
                'redeemed_at' => now()->subDays(7),
                'note' => 'Received and redeemed by demo customer',
            ],
            [
                'code' => 'CHECKOUT-USED-001',
                'initial_value' => 40 * 100,
                'balance' => 15 * 100,
                'status' => GiftCardStatusEnum::ACTIVE,
                'redeemed_by_customer_id' => $demoCustomer->id,
                'note' => 'Used at checkout by demo customer - partial balance remaining',
            ],
            [
                'code' => 'CHECKOUT-USED-002',
                'initial_value' => 60 * 100,
                'balance' => 0,
                'status' => GiftCardStatusEnum::REDEEMED,
                'redeemed_by_customer_id' => $demoCustomer->id,
                'redeemed_at' => now()->subDays(3),
                'note' => 'Used at checkout by demo customer - fully used',
            ],
        ];

        foreach ($customerGiftCards as $data) {
            $isActive = $data['status'] === GiftCardStatusEnum::ACTIVE;

            GiftCard::query()->create(array_merge($data, [
                'currency_code' => $currencyCode,
                'activated_at' => $isActive ? now()->subDays(rand(1, 30)) : null,
                'issued_by' => 1,
            ]));
        }
    }
}
