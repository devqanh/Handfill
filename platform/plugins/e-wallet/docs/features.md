# Features Documentation

Complete guide to all features available in the E-Wallet plugin.

## Table of Contents

1. [Wallet Management](#wallet-management)
2. [Transactions](#transactions)
3. [Top-ups](#top-ups)
4. [Withdrawals](#withdrawals)
5. [Payments](#payments)
6. [Refunds](#refunds)
7. [Admin Features](#admin-features)
8. [Customer Features](#customer-features)

---

## Wallet Management

### Automatic Wallet Creation

Wallets are automatically created for customers when:
- A customer account is created
- A customer makes their first wallet-related action
- An admin adjusts a customer's balance

**Default Values:**
- Initial balance: 0
- Currency: Application default currency

### Balance Tracking

- Balances are stored in **cents** (integer) for precision
- Supports multi-currency (one currency per wallet)
- Real-time balance updates
- Balance before/after tracking for audit trail

### Wallet Properties

```php
$wallet = Wallet::find($id);
$wallet->customer_id;      // Customer ID
$wallet->balance;          // Balance in cents
$wallet->currency_code;    // Currency code (USD, EUR, etc.)
$wallet->formatted_balance; // Formatted balance with currency symbol
```

---

## Transactions

### Transaction Types

The plugin supports six transaction types:

#### 1. Top-up (`top_up`)
- Customer adds funds to wallet
- Always increases balance
- Linked to payment gateway transaction

#### 2. Payment (`payment`)
- Customer pays for order using wallet
- Decreases balance
- Linked to order

#### 3. Refund (`refund`)
- Order refund credited to wallet
- Increases balance
- Linked to original order

#### 4. Admin Adjustment (`admin_adjustment`)
- Manual balance adjustment by admin
- Can increase or decrease balance
- Includes admin notes

#### 5. Vendor Payout (`vendor_payout`)
- Payment to marketplace vendor
- Decreases balance
- Linked to payout record

#### 6. Withdrawal (`withdrawal`)
- Customer withdraws funds
- Decreases balance
- Linked to withdrawal request

### Transaction Properties

```php
$transaction = WalletTransaction::find($id);
$transaction->wallet_id;        // Wallet ID
$transaction->customer_id;      // Customer ID
$transaction->type;             // Transaction type enum
$transaction->status;           // Transaction status enum
$transaction->amount;           // Amount in cents (positive or negative)
$transaction->balance_before;   // Balance before transaction
$transaction->balance_after;    // Balance after transaction
$transaction->reference_type;   // Reference model class
$transaction->reference_id;     // Reference model ID
$transaction->idempotency_key;  // Unique key to prevent duplicates
$transaction->description;      // Human-readable description
$transaction->metadata;         // Additional data (JSON)
```

### Transaction Statuses

- **Pending**: Transaction initiated but not completed
- **Completed**: Transaction successfully processed
- **Failed**: Transaction failed
- **Cancelled**: Transaction cancelled

### Idempotency

Prevent duplicate transactions using idempotency keys:

```php
$transaction = $walletService->credit(
    customerId: $customerId,
    amountCents: 1000,
    type: TransactionTypeEnum::TOP_UP,
    idempotencyKey: 'topup_' . $topupId
);
```

If the same key is used again, a `DuplicateTransactionException` is thrown.

---

## Top-ups

### Customer Top-up Flow

1. Customer navigates to `/customer/e-wallet`
2. Clicks "Top Up Wallet"
3. Enters amount or selects quick amount
4. Reviews exchange rate (if multi-currency)
5. Proceeds to payment method selection
6. Selects payment gateway
7. Completes payment
8. Wallet is credited automatically

### Top-up Statuses

- **Pending**: Top-up created, awaiting payment
- **Processing**: Payment being processed
- **Completed**: Payment successful, wallet credited
- **Failed**: Payment failed
- **Cancelled**: Top-up cancelled

### Quick Select Amounts

Customers can select from preset amounts for convenience. Configure in your theme or customize the view.

### Multi-currency Support

If payment currency differs from wallet currency:
- Exchange rate is displayed
- Customer sees both amounts
- Wallet is credited in wallet currency

### Admin Top-up Management

Admins can:
- View all top-up requests
- Complete pending top-ups manually
- Cancel top-ups
- View payment details

**Actions:**
```
Admin → E-Wallet → Top-ups
```

---

## Withdrawals

### Customer Withdrawal Flow

1. Customer navigates to `/customer/e-wallet`
2. Clicks "Withdraw"
3. Enters withdrawal amount
4. Selects payout method
5. Provides payment details (bank info, PayPal email, etc.)
6. Submits withdrawal request
7. Admin reviews and approves/rejects
8. If approved: funds are processed
9. If rejected: amount is refunded to wallet

### Withdrawal Statuses

- **Pending**: Request submitted, awaiting review
- **Processing**: Approved, payment being processed
- **Completed**: Funds sent to customer
- **Rejected**: Request rejected, funds refunded
- **Cancelled**: Request cancelled

### Payout Methods

#### Bank Transfer
Customer provides:
- Bank name
- Account number
- Account holder name
- Routing number (if applicable)
- SWIFT/BIC code (for international)

#### PayPal
Customer provides:
- PayPal email address

#### Other
Custom payout method with free-form payment details.

### Admin Withdrawal Management

Admins can:
- View all withdrawal requests
- Approve withdrawals
- Reject withdrawals (with automatic refund)
- Update withdrawal status
- Add processing notes

**Workflow:**
```
Pending → Processing → Completed
Pending → Rejected (auto-refund)
```

---

## Payments

### Checkout Integration

Wallet appears as a payment method at checkout when:
- E-Wallet plugin is enabled
- Wallet payment method is enabled
- Customer is logged in
- Customer has sufficient balance

### Payment Flow

1. Customer adds items to cart
2. Proceeds to checkout
3. Selects "Wallet" as payment method
4. Reviews order and wallet balance
5. Confirms payment
6. Wallet balance is deducted
7. Order is created
8. Customer is redirected to success page

### Insufficient Balance

If balance is insufficient:
- Payment method shows warning
- Customer cannot select wallet payment
- Suggested actions: top-up wallet or use another method

### Payment Validation

```php
// Check if customer has sufficient balance
if ($wallet->hasSufficientBalance($amountCents)) {
    // Process payment
}
```

### Payment Transaction

When payment is processed:
- Transaction type: `payment`
- Amount: Negative (debit)
- Reference: Order model
- Description: "Payment for order #ORDER_CODE"

---

## Refunds

### Automatic Refund to Wallet

When an order is refunded:
1. Refund is automatically credited to customer's wallet
2. Transaction is created with type `refund`
3. Customer is notified
4. Customer can use balance or request withdrawal

### Refund Transaction

```php
$transaction = $walletService->credit(
    customerId: $order->customer_id,
    amountCents: $refundAmount,
    type: TransactionTypeEnum::REFUND,
    referenceType: Order::class,
    referenceId: $order->id,
    description: "Refund from order #{$order->code}"
);
```

### Partial Refunds

Partial refunds are supported:
- Only the refunded amount is credited
- Multiple partial refunds create multiple transactions
- Full audit trail maintained

---

## Admin Features

### Dashboard

**Location**: `Admin → E-Wallet → Dashboard`

**Metrics:**
- Total wallets
- Active wallets (balance > 0)
- Total balance in circulation
- Total credits (all time)
- Total debits (all time)
- Transactions today
- Credits today

**Charts:**
- Transaction trends
- Top wallets by balance
- Recent transactions

### Wallet Management

**Location**: `Admin → E-Wallet → Wallets`

**Features:**
- List all wallets
- Search by customer name/email
- Filter by balance
- View wallet details
- View transaction history
- Adjust balance

### Balance Adjustment

Admins can manually adjust customer balances:

1. Navigate to wallet details
2. Click "Adjust Balance"
3. Select adjustment type (Credit or Debit)
4. Enter amount
5. Provide reason
6. Submit

**Transaction Created:**
- Type: `admin_adjustment`
- Metadata includes admin ID
- Reason is stored in description

### Transaction Management

**Location**: `Admin → E-Wallet → Transactions`

**Features:**
- View all transactions
- Filter by type, status, date
- Search by customer
- Export transactions
- View transaction details

### Top-up Management

**Location**: `Admin → E-Wallet → Top-ups`

**Features:**
- View all top-up requests
- Filter by status
- Complete pending top-ups
- Cancel top-ups
- View payment details

### Withdrawal Management

**Location**: `Admin → E-Wallet → Withdrawals`

**Features:**
- View all withdrawal requests
- Filter by status
- Approve withdrawals
- Reject withdrawals
- Add processing notes
- Track payout status

### Permissions

Granular permissions for different admin roles:

- `e-wallet.index` - Access E-Wallet menu
- `e-wallet.wallets.index` - View wallets
- `e-wallet.wallets.adjust` - Adjust balances
- `e-wallet.transactions.index` - View transactions
- `e-wallet.topups.index` - View top-ups
- `e-wallet.topups.complete` - Complete top-ups
- `e-wallet.topups.cancel` - Cancel top-ups
- `e-wallet.withdrawals.index` - View withdrawals
- `e-wallet.withdrawals.approve` - Approve withdrawals
- `e-wallet.withdrawals.reject` - Reject withdrawals
- `e-wallet.settings` - Manage settings

---

## Customer Features

### My Wallet Page

**Location**: `/customer/e-wallet`

**Features:**
- View current balance
- View transaction history
- Top-up wallet button
- Withdraw funds button
- Filter transactions

### Transaction History

Customers can view:
- All their transactions
- Transaction type and status
- Amount and date
- Balance before/after
- Description
- Related orders (clickable links)

### Top-up Wallet

**Location**: `/customer/e-wallet/topup`

**Features:**
- Enter custom amount
- Quick select preset amounts
- View min/max limits
- Select payment method
- Complete payment
- View top-up status

### Request Withdrawal

**Location**: `/customer/e-wallet/withdrawals`

**Features:**
- Enter withdrawal amount
- View available balance
- Select payout method
- Provide payment details
- Submit request
- Track withdrawal status

### Checkout Payment

At checkout, customers can:
- See wallet balance
- Select wallet as payment method
- View remaining balance after payment
- Complete instant checkout

---

## Events

The plugin fires events for integration:

### WalletCredited

Fired when wallet balance increases.

```php
Event::listen(WalletCredited::class, function ($event) {
    $wallet = $event->wallet;
    $transaction = $event->transaction;
    // Your logic here
});
```

### WalletDebited

Fired when wallet balance decreases.

```php
Event::listen(WalletDebited::class, function ($event) {
    $wallet = $event->wallet;
    $transaction = $event->transaction;
    // Your logic here
});
```

### WalletTransactionCreated

Fired for every transaction.

```php
Event::listen(WalletTransactionCreated::class, function ($event) {
    $transaction = $event->transaction;
    // Your logic here
});
```

---

## Security Features

### Transaction Locking

Database row locking prevents race conditions:

```php
DB::transaction(function () {
    $wallet = Wallet::lockForUpdate()->find($id);
    // Perform operations
});
```

### Idempotency Keys

Prevent duplicate transactions:
- Unique key per transaction
- Duplicate key throws exception
- Safe retry logic

### Audit Trail

Complete audit trail for compliance:
- Balance before/after every transaction
- Admin actions logged with user ID
- Timestamps for all operations
- Metadata for additional context

### Permissions

Role-based access control:
- Granular permissions
- Separate read/write permissions
- Admin action tracking

---

## Best Practices

### For Customers

1. **Keep balance reasonable**: Don't store excessive amounts
2. **Monitor transactions**: Check history regularly
3. **Secure account**: Use strong password and 2FA
4. **Verify withdrawals**: Double-check payout details

### For Admins

1. **Review withdrawals**: Verify large withdrawal requests
2. **Monitor suspicious activity**: Unusual transaction patterns
3. **Set appropriate limits**: Min/max amounts based on risk
4. **Regular audits**: Review wallet balances and transactions
5. **Backup data**: Regular database backups

### For Developers

1. **Use idempotency keys**: Prevent duplicate transactions
2. **Handle exceptions**: Catch wallet-specific exceptions
3. **Use events**: Hook into wallet events for custom logic
4. **Test thoroughly**: Test all transaction flows
5. **Log operations**: Comprehensive logging for debugging

---

## Next Steps

- [API Reference](api.md) - Developer integration guide
- [Webhooks](webhooks.md) - Set up webhook notifications
- [Troubleshooting](troubleshooting.md) - Common issues and solutions
