# Installation Guide

This guide will walk you through installing and setting up the E-Wallet plugin.

## Prerequisites

Before installing the E-Wallet plugin, ensure you have:

- ✅ Botble CMS version **7.5.0** or higher
- ✅ **Ecommerce plugin** installed and activated
- ✅ At least one **payment gateway** configured (Stripe, PayPal, etc.)
- ✅ PHP 8.1 or higher
- ✅ MySQL 5.7+ or MariaDB 10.3+

## Installation Steps

### Step 1: Plugin Location

The plugin should be located at:
```
platform/plugins/e-wallet/
```

If you're installing from a package, extract it to this location.

### Step 2: Install Dependencies

If the plugin has composer dependencies:

```bash
cd platform/plugins/e-wallet
composer install --no-dev
```

### Step 3: Run Migrations

Run the database migrations to create the necessary tables:

```bash
php artisan migrate
```

This will create the following tables:
- `ec_wallets` - Stores wallet information
- `ec_wallet_transactions` - Stores all wallet transactions
- `ec_wallet_topups` - Stores top-up requests
- `ec_wallet_withdrawals` - Stores withdrawal requests

### Step 4: Publish Assets

Publish the plugin's public assets:

```bash
php artisan vendor:publish --tag=cms-public --force
```

### Step 5: Clear Cache

Clear the application cache:

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Step 6: Activate the Plugin

1. Log in to your admin panel
2. Navigate to **Admin → Plugins**
3. Find "E-Wallet" in the plugin list
4. Click **Activate**

### Step 7: Configure Permissions

Assign permissions to admin roles:

1. Go to **Admin → System → Roles**
2. Edit the roles that should have access to e-wallet features
3. Grant the following permissions as needed:
   - `e-wallet.index` - Access to E-Wallet menu
   - `e-wallet.wallets.index` - View wallets
   - `e-wallet.wallets.adjust` - Adjust wallet balances
   - `e-wallet.transactions.index` - View transactions
   - `e-wallet.topups.index` - View top-ups
   - `e-wallet.topups.complete` - Complete top-ups
   - `e-wallet.topups.cancel` - Cancel top-ups
   - `e-wallet.withdrawals.index` - View withdrawals
   - `e-wallet.withdrawals.approve` - Approve withdrawals
   - `e-wallet.withdrawals.reject` - Reject withdrawals
   - `e-wallet.settings` - Manage settings

## Post-Installation Configuration

### 1. Enable E-Wallet

1. Navigate to **Admin → E-Wallet → Settings**
2. Check **Enable E-Wallet**
3. Click **Save Settings**

### 2. Configure Top-up Settings

1. In **E-Wallet → Settings**, scroll to **Top-up Settings**
2. Check **Enable Top-up**
3. Set **Minimum Top-up Amount** (e.g., 10)
4. Set **Maximum Top-up Amount** (e.g., 10000)
5. Select **Allowed Payment Methods** (leave empty to allow all)
6. Click **Save Settings**

### 3. Configure Withdrawal Settings

1. In **E-Wallet → Settings**, scroll to **Withdrawal Settings**
2. Check **Enable Withdrawal**
3. Set **Minimum Withdrawal Amount** (e.g., 10)
4. Set **Maximum Withdrawal Amount** (e.g., 10000)
5. Select **Payout Methods** (Bank Transfer, PayPal, etc.)
6. Click **Save Settings**

### 4. Enable Wallet Payment Method

1. Navigate to **Admin → Payments → Payment methods**
2. Find "Wallet" in the payment methods list
3. Click **Edit**
4. Check **Enable**
5. Click **Save**

### 5. Configure Payment Gateways (for Top-ups)

Ensure you have at least one payment gateway configured:

1. Go to **Admin → Payments → Payment methods**
2. Configure Stripe, PayPal, or other gateways
3. These will be available for customers to top-up their wallets

## Verification

After installation, verify everything is working:

### Admin Verification

1. Go to **Admin → E-Wallet**
2. You should see the dashboard with wallet statistics
3. Check that all menu items are accessible:
   - Dashboard
   - Wallets
   - Transactions
   - Top-ups
   - Withdrawals
   - Settings

### Customer Verification

1. Log in as a customer (or create a test customer account)
2. Navigate to `/customer/e-wallet`
3. You should see:
   - Current balance (initially 0)
   - Transaction history (empty)
   - Top-up button
   - Withdraw button

### Test Top-up Flow

1. As a customer, click **Top Up Wallet**
2. Enter an amount
3. Select a payment method
4. Complete the payment
5. Verify the wallet balance is updated

### Test Checkout Payment

1. Add a product to cart
2. Proceed to checkout
3. Select "Wallet" as payment method
4. Complete the order
5. Verify the wallet balance is deducted

## Troubleshooting Installation

### Migration Errors

If you encounter migration errors:

```bash
# Rollback migrations
php artisan migrate:rollback --path=platform/plugins/e-wallet/database/migrations

# Re-run migrations
php artisan migrate --path=platform/plugins/e-wallet/database/migrations
```

### Plugin Not Appearing

If the plugin doesn't appear in the plugins list:

1. Check file permissions:
   ```bash
   chmod -R 755 platform/plugins/e-wallet
   ```

2. Verify the `plugin.json` file exists and is valid

3. Clear cache:
   ```bash
   php artisan cache:clear
   ```

### Assets Not Loading

If CSS/JS assets are not loading:

```bash
# Re-publish assets
php artisan vendor:publish --tag=cms-public --force

# Compile assets if needed
cd platform/plugins/e-wallet
npm install
npm run dev
```

### Permission Denied Errors

If you see permission errors:

```bash
# Fix storage permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Fix ownership (replace www-data with your web server user)
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

## Updating the Plugin

To update the E-Wallet plugin:

1. **Backup your database** before updating
2. Replace the plugin files with the new version
3. Run migrations:
   ```bash
   php artisan migrate
   ```
4. Clear cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```
5. Publish assets:
   ```bash
   php artisan vendor:publish --tag=cms-public --force
   ```

## Uninstallation

To uninstall the E-Wallet plugin:

> ⚠️ **Warning**: This will delete all wallet data, transactions, top-ups, and withdrawals. This action cannot be undone.

1. **Backup your database** first
2. Deactivate the plugin in **Admin → Plugins**
3. Run the rollback migrations:
   ```bash
   php artisan migrate:rollback --path=platform/plugins/e-wallet/database/migrations
   ```
4. Delete the plugin directory:
   ```bash
   rm -rf platform/plugins/e-wallet
   ```
5. Clear cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

## Next Steps

- [Configure the plugin](configuration.md)
- [Learn about features](features.md)
- [Set up webhooks](webhooks.md)
