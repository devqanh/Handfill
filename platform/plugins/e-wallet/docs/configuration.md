# Configuration Guide

This guide covers all configuration options available in the E-Wallet plugin.

## Settings Overview

Access the settings page at: **Admin → E-Wallet → Settings**

## General Settings

### Enable E-Wallet

**Setting**: `enable_e_wallet`  
**Default**: `true`  
**Type**: Boolean

Enable or disable the entire e-wallet functionality. When disabled:
- Customers cannot access their wallets
- Wallet payment method is hidden at checkout
- Top-ups and withdrawals are disabled
- Existing wallet balances are preserved

```php
// Programmatically check if enabled
if (app(WalletHelper::class)->isEnabled()) {
    // E-wallet is enabled
}
```

## Balance Settings

### Allow Negative Balance

**Setting**: `allow_negative_balance`  
**Default**: `false`  
**Type**: Boolean

Allow wallet balances to go below zero. This is useful for:
- Credit/installment payment scenarios
- Buy now, pay later (BNPL) integrations
- Overdraft facilities

> ⚠️ **Warning**: Enabling this removes balance validation. Ensure you have proper credit limit controls in place.

```php
// Check if negative balance is allowed
if (app(WalletHelper::class)->allowNegativeBalance()) {
    // Negative balance is allowed
}
```

## Top-up Settings

### Enable Top-up

**Setting**: `enable_top_up`  
**Default**: `true`  
**Type**: Boolean

Allow customers to add funds to their wallet via payment gateways.

### Minimum Top-up Amount

**Setting**: `min_top_up`  
**Default**: `10`  
**Type**: Integer (in base currency)

The minimum amount customers can add in a single top-up transaction.

```php
// Get minimum top-up amount
$minAmount = app(WalletHelper::class)->getMinTopUp();
```

### Maximum Top-up Amount

**Setting**: `max_top_up`  
**Default**: `100000000`  
**Type**: Integer (in base currency)

The maximum amount customers can add in a single top-up transaction.

```php
// Get maximum top-up amount
$maxAmount = app(WalletHelper::class)->getMaxTopUp();
```

### Allowed Payment Methods for Top-ups

**Setting**: `topup_payment_methods`  
**Default**: All enabled payment methods  
**Type**: Array

Select which payment methods customers can use to top-up their wallet. If none are selected, all enabled payment methods will be available.

> 📝 **Note**: The "Wallet" payment method is automatically excluded from top-up options to prevent circular payments.

```php
// Get allowed top-up payment methods
$methods = get_allowed_topup_payment_methods();

// Check if a specific method is allowed
if (is_topup_payment_method_allowed('stripe')) {
    // Stripe is allowed for top-ups
}
```

## Withdrawal Settings

### Enable Withdrawal

**Setting**: `enable_withdrawal`  
**Default**: `true`  
**Type**: Boolean

Allow customers to request withdrawals from their wallet balance.

### Minimum Withdrawal Amount

**Setting**: `min_withdrawal`  
**Default**: `10`  
**Type**: Integer (in base currency)

The minimum amount customers can withdraw in a single request.

### Maximum Withdrawal Amount

**Setting**: `max_withdrawal`  
**Default**: `100000000`  
**Type**: Integer (in base currency)

The maximum amount customers can withdraw in a single request.

### Payout Methods

**Setting**: `payout_methods`  
**Default**: All methods  
**Type**: Array

Select which payout methods are available for withdrawals:
- **Bank Transfer**: Customer provides bank account details
- **PayPal**: Customer provides PayPal email
- **Other**: Custom payout method

## Payment Method Settings

The wallet can be used as a payment method at checkout. This is configured separately in the payment methods settings.

### Enable Wallet Payment

1. Navigate to **Admin → Payments → Payment methods**
2. Find "Wallet" in the list
3. Click **Edit**
4. Check **Enable**
5. Configure the following:
   - **Name**: Display name (default: "Wallet")
   - **Description**: Customer-facing description
   - **Instructions**: Payment instructions shown to customers

### Payment Method Configuration

```php
// Check if wallet payment is enabled
$isEnabled = get_payment_setting('status', E_WALLET_PAYMENT_METHOD_NAME);
```

## Webhook Settings

Configure webhooks to receive notifications when wallet events occur.

### Enable Webhooks

**Setting**: `enable_webhooks`  
**Default**: `false`  
**Type**: Boolean

Enable sending webhook notifications for wallet events.

### Webhook URLs

Configure URLs for different events:

#### Top-up Created Webhook URL
**Setting**: `topup_created_url`  
Triggered when a customer initiates a new top-up request.

#### Top-up Completed Webhook URL
**Setting**: `topup_completed_url`  
Triggered when a top-up payment is successfully completed and wallet is credited.

#### Top-up Failed Webhook URL
**Setting**: `topup_failed_url`  
Triggered when a top-up payment fails.

#### Top-up Cancelled Webhook URL
**Setting**: `topup_cancelled_url`  
Triggered when a top-up is cancelled by admin or customer.

See [Webhooks Documentation](webhooks.md) for payload details and testing.

## Refund Behavior

> 📝 **Important**: When E-Wallet is enabled, ALL refunds are automatically credited to the customer's wallet.

This is mandatory behavior and cannot be changed. Customers can:
- Use the refunded balance for future purchases
- Request a withdrawal to get the money back

## Advanced Configuration

### Programmatic Settings

You can get and set wallet settings programmatically:

```php
// Get a setting
$value = get_wallet_setting('enable_e_wallet', true);

// Set a setting
set_wallet_setting('min_top_up', 20);
```

### Default Currency

The wallet uses the application's default currency:

```php
$currency = app(WalletHelper::class)->getDefaultCurrency();
// Returns: 'USD', 'EUR', etc.
```

### View Paths

The plugin supports theme overrides for views:

```php
$viewPath = app(WalletHelper::class)->viewPath('wallet');
// Returns: 'theme::views.e-wallet.wallet' if exists
// Otherwise: 'plugins/e-wallet::themes.wallet'
```

## Environment Variables

You can set default values via environment variables:

```env
# .env file
E_WALLET_ENABLED=true
E_WALLET_MIN_TOPUP=10
E_WALLET_MAX_TOPUP=10000
E_WALLET_MIN_WITHDRAWAL=10
E_WALLET_MAX_WITHDRAWAL=10000
```

Then in your settings configuration:

```php
'enable_e_wallet' => env('E_WALLET_ENABLED', true),
'min_top_up' => env('E_WALLET_MIN_TOPUP', 10),
```

## Database Configuration

All settings are stored in the `settings` table with the prefix `e_wallet_`:

```sql
SELECT * FROM settings WHERE key LIKE 'e_wallet_%';
```

## Configuration Best Practices

### 1. Set Appropriate Limits

- Set minimum amounts to prevent micro-transactions
- Set maximum amounts based on your risk tolerance
- Consider fraud prevention when setting limits

### 2. Payment Method Selection

- Only enable trusted payment gateways for top-ups
- Test each payment method thoroughly
- Monitor for failed transactions

### 3. Withdrawal Approval Process

- Implement a manual approval process for large withdrawals
- Set up email notifications for withdrawal requests
- Verify customer identity for first-time withdrawals

### 4. Webhook Security

- Use HTTPS URLs for webhooks
- Implement signature verification
- Log all webhook requests
- Set up monitoring for failed webhooks

### 5. Testing Configuration

Always test configuration changes:

```bash
# Test top-up flow
1. Create test customer account
2. Attempt top-up with minimum amount
3. Attempt top-up with maximum amount
4. Attempt top-up below minimum (should fail)
5. Attempt top-up above maximum (should fail)

# Test withdrawal flow
1. Add balance to test account
2. Request withdrawal with minimum amount
3. Request withdrawal with maximum amount
4. Request withdrawal exceeding balance (should fail)
```

## Configuration Checklist

Use this checklist when setting up the plugin:

- [ ] Enable E-Wallet
- [ ] Set minimum top-up amount
- [ ] Set maximum top-up amount
- [ ] Select allowed payment methods for top-ups
- [ ] Enable withdrawal functionality
- [ ] Set minimum withdrawal amount
- [ ] Set maximum withdrawal amount
- [ ] Select available payout methods
- [ ] Enable wallet payment method
- [ ] Configure webhook URLs (optional)
- [ ] Test top-up flow
- [ ] Test payment flow
- [ ] Test withdrawal flow
- [ ] Test refund flow
- [ ] Configure admin permissions
- [ ] Set up email notifications

## Troubleshooting Configuration

### Settings Not Saving

If settings are not saving:

1. Check file permissions on `storage/` directory
2. Clear cache: `php artisan cache:clear`
3. Check database connection
4. Review error logs

### Payment Method Not Showing

If wallet payment method is not showing at checkout:

1. Verify E-Wallet is enabled in settings
2. Verify Wallet payment method is enabled in payment methods
3. Ensure customer is logged in
4. Check that customer has sufficient balance
5. Clear cache

### Top-up Payment Methods Not Appearing

If payment methods are not showing for top-ups:

1. Verify at least one payment gateway is configured
2. Check that payment gateways are enabled
3. Verify `topup_payment_methods` setting
4. Ensure E_WALLET_PAYMENT_METHOD_NAME is excluded

## Next Steps

- [Learn about features](features.md)
- [API Reference](api.md)
- [Set up webhooks](webhooks.md)
