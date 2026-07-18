# API Reference

Developer guide for integrating with the E-Wallet plugin programmatically.

## Table of Contents

1. [Services](#services)
2. [Models](#models)
3. [Helpers](#helpers)
4. [Events](#events)
5. [Exceptions](#exceptions)
6. [Enums](#enums)

---

## Services

### WalletService

Main service for wallet operations.

#### Get or Create Wallet

```php
use Botble\EWallet\Services\WalletService;

$walletService = app(WalletService::class);
$wallet = $walletService->getOrCreateWallet($customerId);
```

#### Get Balance

```php
$balanceInCents = $walletService->getBalance($customerId);
```

#### Credit Wallet

Add funds to wallet:

```php
$transaction = $walletService->credit(
    customerId: $customerId,
    amountCents: 1000, // $10.00
    type: TransactionTypeEnum::TOP_UP,
    referenceType: WalletTopUp::class, // Optional
    referenceId: $topupId, // Optional
    description: 'Wallet top-up', // Optional
    idempotencyKey: 'unique_key', // Optional
    metadata: ['key' => 'value'] // Optional
);
```

**Returns**: `WalletTransaction`

**Throws**: `DuplicateTransactionException` if idempotency key already exists

#### Debit Wallet

Deduct funds from wallet:

```php
$transaction = $walletService->debit(
    customerId: $customerId,
    amountCents: 500, // $5.00
    type: TransactionTypeEnum::PAYMENT,
    referenceType: Order::class, // Optional
    referenceId: $orderId, // Optional
    description: 'Order payment', // Optional
    idempotencyKey: 'unique_key', // Optional
    metadata: ['key' => 'value'] // Optional
);
```

**Returns**: `WalletTransaction`

**Throws**: 
- `InsufficientBalanceException` if balance is insufficient
- `DuplicateTransactionException` if idempotency key already exists

#### Adjust Balance

Admin balance adjustment (can be positive or negative):

```php
$transaction = $walletService->adjustBalance(
    customerId: $customerId,
    amountCents: 1000, // Positive = credit, Negative = debit
    description: 'Admin adjustment', // Optional
    createdBy: $adminId, // Optional
    metadata: ['reason' => 'Compensation'] // Optional
);
```

#### Check Transaction Exists

```php
$exists = $walletService->transactionExists('idempotency_key');
```

#### Find Transaction by Idempotency Key

```php
$transaction = $walletService->findTransactionByIdempotencyKey('key');
```

#### Get Transactions by Order

```php
$transactions = $walletService->getTransactionsByOrder($order);
```

---

### TopUpService

Service for managing top-up operations.

```php
use Botble\EWallet\Services\TopUpService;

$topUpService = app(TopUpService::class);
```

#### Create Top-up

```php
$topUp = $topUpService->create([
    'customer_id' => $customerId,
    'amount' => 1000, // Amount in cents
    'currency_code' => 'USD',
    'payment_currency' => 'USD',
    'exchange_rate' => 1.0,
]);
```

#### Complete Top-up

```php
$topUpService->complete($topUp, $paymentId);
```

#### Cancel Top-up

```php
$topUpService->cancel($topUp);
```

---

### WalletPaymentService

Service for processing wallet payments at checkout.

```php
use Botble\EWallet\Services\WalletPaymentService;

$paymentService = app(WalletPaymentService::class);
```

#### Process Payment

```php
$result = $paymentService->processPayment($order);
```

---

### WebhookService

Service for sending webhook notifications.

```php
use Botble\EWallet\Services\WebhookService;

$webhookService = app(WebhookService::class);
```

#### Send Webhook

```php
$webhookService->send($url, $payload);
```

---

## Models

### Wallet

```php
use Botble\EWallet\Models\Wallet;

// Properties
$wallet->id;
$wallet->customer_id;
$wallet->balance; // In cents
$wallet->currency_code;
$wallet->created_at;
$wallet->updated_at;

// Relationships
$wallet->customer; // BelongsTo Customer
$wallet->transactions; // HasMany WalletTransaction
$wallet->withdrawals; // HasMany Withdrawal

// Accessors
$wallet->formatted_balance; // Formatted with currency symbol

// Methods
$wallet->hasSufficientBalance(1000); // Check if balance >= amount
```

### WalletTransaction

```php
use Botble\EWallet\Models\WalletTransaction;

// Properties
$transaction->id;
$transaction->wallet_id;
$transaction->customer_id;
$transaction->type; // TransactionTypeEnum
$transaction->status; // TransactionStatusEnum
$transaction->amount; // In cents (positive or negative)
$transaction->balance_before; // In cents
$transaction->balance_after; // In cents
$transaction->reference_type; // Model class name
$transaction->reference_id; // Model ID
$transaction->idempotency_key;
$transaction->description;
$transaction->metadata; // Array
$transaction->created_at;
$transaction->updated_at;

// Relationships
$transaction->wallet; // BelongsTo Wallet
$transaction->customer; // BelongsTo Customer
```

### WalletTopUp

```php
use Botble\EWallet\Models\WalletTopUp;

// Properties
$topUp->id;
$topUp->code; // Unique code
$topUp->customer_id;
$topUp->amount; // In cents
$topUp->currency_code;
$topUp->payment_amount; // In payment currency cents
$topUp->payment_currency;
$topUp->exchange_rate;
$topUp->status; // TopUpStatusEnum
$topUp->payment_id;
$topUp->created_at;
$topUp->updated_at;

// Relationships
$topUp->customer; // BelongsTo Customer
```

### Withdrawal

```php
use Botble\EWallet\Models\Withdrawal;

// Properties
$withdrawal->id;
$withdrawal->wallet_id;
$withdrawal->customer_id;
$withdrawal->amount; // In cents
$withdrawal->currency_code;
$withdrawal->status; // WithdrawalStatusEnum
$withdrawal->payment_channel; // PayoutPaymentMethodsEnum
$withdrawal->payment_details;
$withdrawal->bank_info; // Array
$withdrawal->notes;
$withdrawal->transaction_id;
$withdrawal->processed_by; // Admin user ID
$withdrawal->processed_at;
$withdrawal->created_at;
$withdrawal->updated_at;

// Relationships
$withdrawal->wallet; // BelongsTo Wallet
$withdrawal->customer; // BelongsTo Customer

// Accessors
$withdrawal->formatted_amount;

// Methods
$withdrawal->canEditStatus(); // Check if status can be changed
$withdrawal->getNextStatuses(); // Get available next statuses
```

---

## Helpers

### WalletHelper

```php
use Botble\EWallet\Helpers\WalletHelper;

$helper = app(WalletHelper::class);

// Check if e-wallet is enabled
$helper->isEnabled(); // bool

// Check if negative balance is allowed
$helper->allowNegativeBalance(); // bool

// Check if top-up is enabled
$helper->isTopUpEnabled(); // bool

// Get minimum top-up amount
$helper->getMinTopUp(); // int

// Get maximum top-up amount
$helper->getMaxTopUp(); // int

// Get default currency
$helper->getDefaultCurrency(); // string

// Get view path (with theme override support)
$helper->viewPath('wallet'); // string
```

### Setting Helpers

```php
// Get wallet setting
$value = get_wallet_setting('enable_e_wallet', true);

// Set wallet setting
set_wallet_setting('min_top_up', 20);

// Get allowed top-up payment methods
$methods = get_allowed_topup_payment_methods(); // array

// Check if payment method is allowed for top-ups
$allowed = is_topup_payment_method_allowed('stripe'); // bool
```

---

## Events

### WalletCredited

Fired when wallet balance increases.

```php
namespace Botble\EWallet\Events;

class WalletCredited
{
    public function __construct(
        public Wallet $wallet,
        public WalletTransaction $transaction
    ) {}
}
```

**Usage:**

```php
use Botble\EWallet\Events\WalletCredited;
use Illuminate\Support\Facades\Event;

Event::listen(WalletCredited::class, function (WalletCredited $event) {
    $wallet = $event->wallet;
    $transaction = $event->transaction;
    
    // Send notification
    // Update external system
    // Log activity
});
```

### WalletDebited

Fired when wallet balance decreases.

```php
namespace Botble\EWallet\Events;

class WalletDebited
{
    public function __construct(
        public Wallet $wallet,
        public WalletTransaction $transaction
    ) {}
}
```

### WalletTransactionCreated

Fired for every transaction (credit or debit).

```php
namespace Botble\EWallet\Events;

class WalletTransactionCreated
{
    public function __construct(
        public WalletTransaction $transaction
    ) {}
}
```

---

## Exceptions

### InsufficientBalanceException

Thrown when attempting to debit more than available balance.

```php
use Botble\EWallet\Exceptions\InsufficientBalanceException;

try {
    $walletService->debit($customerId, 10000, TransactionTypeEnum::PAYMENT);
} catch (InsufficientBalanceException $e) {
    $required = $e->getRequiredAmount(); // Amount needed
    $available = $e->getAvailableBalance(); // Current balance
    
    // Handle insufficient balance
}
```

### DuplicateTransactionException

Thrown when idempotency key already exists.

```php
use Botble\EWallet\Exceptions\DuplicateTransactionException;

try {
    $walletService->credit($customerId, 1000, 'top_up', idempotencyKey: 'key123');
} catch (DuplicateTransactionException $e) {
    $key = $e->getIdempotencyKey();
    
    // Transaction already processed
}
```

### WalletNotFoundException

Thrown when wallet is not found.

```php
use Botble\EWallet\Exceptions\WalletNotFoundException;

try {
    // Some operation
} catch (WalletNotFoundException $e) {
    $customerId = $e->getCustomerId();
    
    // Handle missing wallet
}
```

---

## Enums

### TransactionTypeEnum

```php
use Botble\EWallet\Enums\TransactionTypeEnum;

TransactionTypeEnum::TOP_UP;           // 'top_up'
TransactionTypeEnum::PAYMENT;          // 'payment'
TransactionTypeEnum::REFUND;           // 'refund'
TransactionTypeEnum::ADMIN_ADJUSTMENT; // 'admin_adjustment'
TransactionTypeEnum::VENDOR_PAYOUT;    // 'vendor_payout'
TransactionTypeEnum::WITHDRAWAL;       // 'withdrawal'

// Get label
$label = TransactionTypeEnum::TOP_UP()->label();

// Get color
$color = TransactionTypeEnum::TOP_UP()->color(); // 'success'

// Get badge HTML
$badge = TransactionTypeEnum::TOP_UP()->badge();
```

### TransactionStatusEnum

```php
use Botble\EWallet\Enums\TransactionStatusEnum;

TransactionStatusEnum::PENDING;    // 'pending'
TransactionStatusEnum::COMPLETED;  // 'completed'
TransactionStatusEnum::FAILED;     // 'failed'
TransactionStatusEnum::CANCELLED;  // 'cancelled'
```

### TopUpStatusEnum

```php
use Botble\EWallet\Enums\TopUpStatusEnum;

TopUpStatusEnum::PENDING;     // 'pending'
TopUpStatusEnum::PROCESSING;  // 'processing'
TopUpStatusEnum::COMPLETED;   // 'completed'
TopUpStatusEnum::FAILED;      // 'failed'
TopUpStatusEnum::CANCELLED;   // 'cancelled'
```

### WithdrawalStatusEnum

```php
use Botble\EWallet\Enums\WithdrawalStatusEnum;

WithdrawalStatusEnum::PENDING;     // 'pending'
WithdrawalStatusEnum::PROCESSING;  // 'processing'
WithdrawalStatusEnum::COMPLETED;   // 'completed'
WithdrawalStatusEnum::REJECTED;    // 'rejected'
WithdrawalStatusEnum::CANCELLED;   // 'cancelled'
```

### PayoutPaymentMethodsEnum

```php
use Botble\EWallet\Enums\PayoutPaymentMethodsEnum;

PayoutPaymentMethodsEnum::BANK_TRANSFER; // 'bank_transfer'
PayoutPaymentMethodsEnum::PAYPAL;        // 'paypal'
PayoutPaymentMethodsEnum::OTHER;         // 'other'
```

---

## Usage Examples

### Example 1: Process Order Payment with Wallet

```php
use Botble\EWallet\Services\WalletService;
use Botble\EWallet\Enums\TransactionTypeEnum;
use Botble\EWallet\Exceptions\InsufficientBalanceException;

$walletService = app(WalletService::class);

try {
    $transaction = $walletService->debit(
        customerId: $order->customer_id,
        amountCents: $order->amount * 100,
        type: TransactionTypeEnum::PAYMENT,
        referenceType: Order::class,
        referenceId: $order->id,
        description: "Payment for order #{$order->code}",
        idempotencyKey: "order_payment_{$order->id}"
    );
    
    // Update order status
    $order->update(['payment_status' => 'completed']);
    
} catch (InsufficientBalanceException $e) {
    // Handle insufficient balance
    return redirect()->back()->with('error', 'Insufficient wallet balance');
}
```

### Example 2: Process Refund to Wallet

```php
use Botble\EWallet\Services\WalletService;
use Botble\EWallet\Enums\TransactionTypeEnum;

$walletService = app(WalletService::class);

$transaction = $walletService->credit(
    customerId: $order->customer_id,
    amountCents: $refundAmount * 100,
    type: TransactionTypeEnum::REFUND,
    referenceType: Order::class,
    referenceId: $order->id,
    description: "Refund from order #{$order->code}",
    idempotencyKey: "order_refund_{$order->id}_{$refundId}"
);

// Send notification to customer
Mail::to($order->customer->email)->send(new RefundNotification($transaction));
```

### Example 3: Listen to Wallet Events

```php
// In EventServiceProvider or a listener

use Botble\EWallet\Events\WalletCredited;
use Illuminate\Support\Facades\Log;

Event::listen(WalletCredited::class, function (WalletCredited $event) {
    Log::info('Wallet credited', [
        'customer_id' => $event->wallet->customer_id,
        'amount' => $event->transaction->amount,
        'type' => $event->transaction->type,
        'balance_after' => $event->wallet->balance,
    ]);
    
    // Send email notification
    // Update analytics
    // Trigger webhooks
});
```

### Example 4: Custom Transaction with Metadata

```php
$transaction = $walletService->credit(
    customerId: $customerId,
    amountCents: 5000,
    type: TransactionTypeEnum::ADMIN_ADJUSTMENT,
    description: 'Promotional credit',
    metadata: [
        'promotion_id' => 123,
        'campaign' => 'summer_sale',
        'admin_id' => auth()->id(),
        'reason' => 'Customer loyalty reward',
    ]
);
```

### Example 5: Check Balance Before Operation

```php
use Botble\EWallet\Services\WalletService;

$walletService = app(WalletService::class);
$wallet = $walletService->getOrCreateWallet($customerId);

$requiredAmount = 5000; // $50.00

if ($wallet->hasSufficientBalance($requiredAmount)) {
    // Process payment
    $transaction = $walletService->debit(
        customerId: $customerId,
        amountCents: $requiredAmount,
        type: TransactionTypeEnum::PAYMENT
    );
} else {
    // Show error or redirect to top-up
    $shortfall = $requiredAmount - $wallet->balance;
    return redirect()->route('customer.e-wallet.topup.create')
        ->with('info', "Please add {$wallet->formatted_balance} to your wallet");
}
```

---

## Database Schema

### ec_wallets

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| customer_id | bigint | Foreign key to customers |
| balance | bigint | Balance in cents |
| currency_code | varchar(3) | Currency code (USD, EUR, etc.) |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

### ec_wallet_transactions

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| wallet_id | bigint | Foreign key to wallets |
| customer_id | bigint | Foreign key to customers |
| type | varchar(50) | Transaction type |
| status | varchar(50) | Transaction status |
| amount | bigint | Amount in cents (+ or -) |
| balance_before | bigint | Balance before transaction |
| balance_after | bigint | Balance after transaction |
| reference_type | varchar(255) | Referenced model class |
| reference_id | bigint | Referenced model ID |
| idempotency_key | varchar(255) | Unique key |
| description | text | Description |
| metadata | json | Additional data |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

### ec_wallet_topups

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| code | varchar(50) | Unique code |
| customer_id | bigint | Foreign key to customers |
| amount | bigint | Amount in cents |
| currency_code | varchar(3) | Wallet currency |
| payment_amount | bigint | Payment amount in cents |
| payment_currency | varchar(3) | Payment currency |
| exchange_rate | decimal(20,10) | Exchange rate |
| status | varchar(50) | Top-up status |
| payment_id | varchar(255) | Payment gateway ID |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

### ec_wallet_withdrawals

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| wallet_id | bigint | Foreign key to wallets |
| customer_id | bigint | Foreign key to customers |
| amount | bigint | Amount in cents |
| currency_code | varchar(3) | Currency code |
| status | varchar(50) | Withdrawal status |
| payment_channel | varchar(50) | Payout method |
| payment_details | text | Payment details |
| bank_info | json | Bank information |
| notes | text | Admin notes |
| transaction_id | bigint | Related transaction ID |
| processed_by | bigint | Admin user ID |
| processed_at | timestamp | Processing timestamp |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

---

## Next Steps

- [Webhooks Documentation](webhooks.md)
- [Troubleshooting Guide](troubleshooting.md)
