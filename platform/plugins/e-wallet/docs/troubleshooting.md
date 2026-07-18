# Troubleshooting Guide

Common issues and solutions for the E-Wallet plugin.

## Table of Contents

1. [Installation Issues](#installation-issues)
2. [Configuration Issues](#configuration-issues)
3. [Top-up Issues](#top-up-issues)
4. [Payment Issues](#payment-issues)
5. [Withdrawal Issues](#withdrawal-issues)
6. [Balance Issues](#balance-issues)
7. [Performance Issues](#performance-issues)
8. [Error Messages](#error-messages)

---

## Installation Issues

### Plugin Not Appearing in Admin

**Symptoms**: E-Wallet plugin doesn't show in the plugins list

**Solutions**:

1. Check file permissions:
   ```bash
   chmod -R 755 platform/plugins/e-wallet
   ```

2. Verify `plugin.json` exists and is valid:
   ```bash
   cat platform/plugins/e-wallet/plugin.json
   ```

3. Clear cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

4. Check minimum core version requirement (7.5.0+)

### Migration Errors

**Symptoms**: Errors when running migrations

**Solutions**:

1. Check database connection in `.env`
2. Ensure ecommerce plugin is installed first
3. Run migrations with verbose output:
   ```bash
   php artisan migrate --path=platform/plugins/e-wallet/database/migrations -vvv
   ```

4. If tables already exist, rollback and retry:
   ```bash
   php artisan migrate:rollback --path=platform/plugins/e-wallet/database/migrations
   php artisan migrate --path=platform/plugins/e-wallet/database/migrations
   ```

### Assets Not Loading

**Symptoms**: CSS/JS files not loading, broken styling

**Solutions**:

1. Publish assets:
   ```bash
   php artisan vendor:publish --tag=cms-public --force
   ```

2. Clear view cache:
   ```bash
   php artisan view:clear
   ```

3. Check file permissions:
   ```bash
   chmod -R 755 public/vendor/core/plugins/e-wallet
   ```

---

## Configuration Issues

### Settings Not Saving

**Symptoms**: Changes to settings are not persisted

**Solutions**:

1. Check storage permissions:
   ```bash
   chmod -R 775 storage
   chown -R www-data:www-data storage
   ```

2. Clear config cache:
   ```bash
   php artisan config:clear
   ```

3. Check database connection
4. Review error logs in `storage/logs`

### Payment Method Not Showing

**Symptoms**: Wallet payment method not available at checkout

**Solutions**:

1. Verify E-Wallet is enabled:
   - Go to **Admin → E-Wallet → Settings**
   - Check "Enable E-Wallet" is checked

2. Verify Wallet payment method is enabled:
   - Go to **Admin → Payments → Payment methods**
   - Find "Wallet" and ensure it's enabled

3. Ensure customer is logged in (wallet requires authentication)

4. Check customer has sufficient balance

5. Clear cache:
   ```bash
   php artisan cache:clear
   ```

---

## Top-up Issues

### Top-up Form Not Showing

**Symptoms**: Cannot access top-up page

**Solutions**:

1. Verify top-up is enabled:
   - **Admin → E-Wallet → Settings**
   - Check "Enable Top-up"

2. Check customer is logged in

3. Clear route cache:
   ```bash
   php artisan route:clear
   ```

### No Payment Methods Available

**Symptoms**: No payment methods shown on top-up checkout

**Solutions**:

1. Ensure at least one payment gateway is configured:
   - **Admin → Payments → Payment methods**
   - Enable Stripe, PayPal, or other gateways

2. Check allowed payment methods setting:
   - **Admin → E-Wallet → Settings → Top-up Settings**
   - Verify "Allowed Payment Methods" includes your gateways

3. Verify payment gateways are properly configured with API keys

### Top-up Not Completing

**Symptoms**: Payment successful but wallet not credited

**Solutions**:

1. Check webhook configuration for payment gateway
2. Review payment logs in `storage/logs`
3. Manually complete top-up:
   - **Admin → E-Wallet → Top-ups**
   - Find the pending top-up
   - Click "Complete"

4. Check for errors in transaction table:
   ```sql
   SELECT * FROM ec_wallet_transactions 
   WHERE reference_type = 'Botble\\EWallet\\Models\\WalletTopUp'
   ORDER BY created_at DESC LIMIT 10;
   ```

### Amount Validation Errors

**Symptoms**: "Amount must be at least X" or "Amount cannot exceed Y"

**Solutions**:

1. Check min/max top-up settings:
   - **Admin → E-Wallet → Settings → Top-up Settings**
   - Adjust "Minimum Top-up Amount" and "Maximum Top-up Amount"

2. Ensure amount is within configured range

---

## Payment Issues

### Insufficient Balance Error

**Symptoms**: "Insufficient wallet balance" at checkout

**Solutions**:

1. Customer needs to top-up wallet:
   - Navigate to `/customer/e-wallet`
   - Click "Top Up Wallet"

2. Check actual balance:
   ```sql
   SELECT * FROM ec_wallets WHERE customer_id = ?;
   ```

3. If balance is incorrect, admin can adjust:
   - **Admin → E-Wallet → Wallets**
   - Find customer wallet
   - Click "Adjust Balance"

### Payment Deducted But Order Failed

**Symptoms**: Wallet debited but order not created

**Solutions**:

1. Check for duplicate transactions:
   ```sql
   SELECT * FROM ec_wallet_transactions 
   WHERE customer_id = ? 
   AND type = 'payment'
   ORDER BY created_at DESC;
   ```

2. Refund the amount:
   ```php
   $walletService->credit(
       customerId: $customerId,
       amountCents: $amount,
       type: TransactionTypeEnum::REFUND,
       description: 'Refund for failed order'
   );
   ```

3. Review error logs for order creation failure

### Wallet Payment Not Deducting

**Symptoms**: Order created but wallet balance unchanged

**Solutions**:

1. Check payment status in order:
   ```sql
   SELECT payment_status, payment_channel 
   FROM ec_orders WHERE id = ?;
   ```

2. Check for transaction record:
   ```sql
   SELECT * FROM ec_wallet_transactions 
   WHERE reference_type = 'Botble\\Ecommerce\\Models\\Order'
   AND reference_id = ?;
   ```

3. If missing, manually process payment:
   ```php
   $walletService->debit(
       customerId: $order->customer_id,
       amountCents: $order->amount * 100,
       type: TransactionTypeEnum::PAYMENT,
       referenceType: Order::class,
       referenceId: $order->id,
       description: "Payment for order #{$order->code}"
   );
   ```

---

## Withdrawal Issues

### Withdrawal Form Not Showing

**Symptoms**: Cannot access withdrawal page

**Solutions**:

1. Verify withdrawal is enabled:
   - **Admin → E-Wallet → Settings**
   - Check "Enable Withdrawal"

2. Check customer is logged in

3. Verify customer has balance > 0

### Withdrawal Request Fails

**Symptoms**: Error when submitting withdrawal request

**Solutions**:

1. Check withdrawal amount limits:
   - **Admin → E-Wallet → Settings → Withdrawal Settings**
   - Verify min/max amounts

2. Ensure customer has sufficient balance

3. Check payment method is configured:
   - Verify payout methods are selected in settings

4. Review validation errors in logs

### Withdrawal Not Processing

**Symptoms**: Withdrawal stuck in "pending" status

**Solutions**:

1. Admin must manually approve:
   - **Admin → E-Wallet → Withdrawals**
   - Find the withdrawal request
   - Click "Approve"

2. Check withdrawal status workflow:
   - Pending → Processing → Completed
   - Ensure status transitions are allowed

---

## Balance Issues

### Balance Not Updating

**Symptoms**: Transactions created but balance unchanged

**Solutions**:

1. Check transaction was completed:
   ```sql
   SELECT * FROM ec_wallet_transactions 
   WHERE id = ? AND status = 'completed';
   ```

2. Verify balance_after matches current balance:
   ```sql
   SELECT w.balance, t.balance_after 
   FROM ec_wallets w
   JOIN ec_wallet_transactions t ON t.wallet_id = w.id
   WHERE w.id = ?
   ORDER BY t.created_at DESC LIMIT 1;
   ```

3. If mismatch, recalculate balance:
   ```php
   $wallet = Wallet::find($walletId);
   $correctBalance = WalletTransaction::where('wallet_id', $walletId)
       ->where('status', 'completed')
       ->sum('amount');
   $wallet->update(['balance' => $correctBalance]);
   ```

### Negative Balance When Not Allowed

**Symptoms**: Balance goes negative despite setting disabled

**Solutions**:

1. Check "Allow Negative Balance" setting:
   - **Admin → E-Wallet → Settings**
   - Ensure it's unchecked

2. Review recent transactions for errors:
   ```sql
   SELECT * FROM ec_wallet_transactions 
   WHERE wallet_id = ?
   ORDER BY created_at DESC LIMIT 10;
   ```

3. Adjust balance if needed:
   - **Admin → E-Wallet → Wallets**
   - Click "Adjust Balance"

### Balance Mismatch

**Symptoms**: Displayed balance doesn't match database

**Solutions**:

1. Clear cache:
   ```bash
   php artisan cache:clear
   ```

2. Refresh page with Ctrl+F5

3. Check for JavaScript errors in browser console

4. Verify database value:
   ```sql
   SELECT balance FROM ec_wallets WHERE id = ?;
   ```

---

## Performance Issues

### Slow Wallet Page Load

**Symptoms**: Wallet dashboard takes long to load

**Solutions**:

1. Add database indexes:
   ```sql
   CREATE INDEX idx_wallet_customer ON ec_wallets(customer_id);
   CREATE INDEX idx_transaction_wallet ON ec_wallet_transactions(wallet_id);
   CREATE INDEX idx_transaction_customer ON ec_wallet_transactions(customer_id);
   ```

2. Limit transaction history display:
   - Show only recent 50 transactions
   - Add pagination

3. Enable query caching:
   ```php
   $transactions = WalletTransaction::where('wallet_id', $walletId)
       ->latest()
       ->limit(50)
       ->remember(60) // Cache for 60 seconds
       ->get();
   ```

### Slow Transaction Processing

**Symptoms**: Payments take long to process

**Solutions**:

1. Check database locks:
   ```sql
   SHOW PROCESSLIST;
   ```

2. Optimize transaction queries

3. Use queue for async processing:
   ```php
   ProcessWalletPayment::dispatch($order);
   ```

4. Add database indexes on frequently queried columns

---

## Error Messages

### "Wallet not found for customer ID: X"

**Cause**: Wallet doesn't exist for customer

**Solution**:
```php
$wallet = app(WalletService::class)->getOrCreateWallet($customerId);
```

### "Insufficient wallet balance. Required: X, Available: Y"

**Cause**: Customer balance is less than required amount

**Solutions**:
1. Customer should top-up wallet
2. Admin can adjust balance
3. Use different payment method

### "This transaction has already been processed"

**Cause**: Duplicate idempotency key

**Solutions**:
1. This is expected behavior (prevents duplicates)
2. If legitimate retry needed, use different idempotency key
3. Check if original transaction succeeded

### "E-Wallet is currently disabled"

**Cause**: Plugin is disabled in settings

**Solution**:
- **Admin → E-Wallet → Settings**
- Check "Enable E-Wallet"

### "Customer account required for wallet payment"

**Cause**: Guest checkout attempted with wallet payment

**Solution**:
- Customer must be logged in to use wallet
- Redirect to login page

### "Amount must be at least X"

**Cause**: Amount below minimum threshold

**Solution**:
- Increase amount to meet minimum
- Admin can adjust minimum in settings

### "Amount cannot exceed X"

**Cause**: Amount above maximum threshold

**Solution**:
- Reduce amount to meet maximum
- Admin can adjust maximum in settings
- Split into multiple transactions

---

## Debugging Tips

### Enable Debug Mode

```env
# .env
APP_DEBUG=true
APP_LOG_LEVEL=debug
```

### Check Logs

```bash
# Application logs
tail -f storage/logs/laravel.log

# Web server logs
tail -f /var/log/nginx/error.log
tail -f /var/log/apache2/error.log
```

### Database Queries

Enable query logging:

```php
DB::enableQueryLog();
// Your code here
dd(DB::getQueryLog());
```

### Clear All Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### Check Queue Workers

If using queues:

```bash
# Check queue status
php artisan queue:work --once

# Restart queue workers
php artisan queue:restart
```

---

## Getting Help

If you're still experiencing issues:

1. **Check documentation**: Review all docs thoroughly
2. **Search error messages**: Google the exact error message
3. **Check logs**: Review `storage/logs/laravel.log`
4. **Contact support**: Reach out to Botble Technologies support with:
   - Detailed description of the issue
   - Steps to reproduce
   - Error messages and logs
   - Environment details (PHP version, database, etc.)

---

## Next Steps

- [Features Documentation](features.md)
- [API Reference](api.md)
- [Configuration Guide](configuration.md)
