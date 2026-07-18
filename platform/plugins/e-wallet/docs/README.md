# E-Wallet Plugin Documentation

Welcome to the E-Wallet plugin documentation. This plugin provides a comprehensive digital wallet system for customer payments, refunds, top-ups, and withdrawals.

## Table of Contents

1. [Installation](installation.md)
2. [Configuration](configuration.md)
3. [Features](features.md)
4. [API Reference](api.md)
5. [Webhooks](webhooks.md)
6. [Troubleshooting](troubleshooting.md)
7. [Changelog](changelog.md)

## Quick Start

### Overview

The E-Wallet plugin integrates seamlessly with the ecommerce plugin to provide:

- **Wallet Management**: Automatic wallet creation and balance tracking
- **Top-ups**: Allow customers to add funds via payment gateways
- **Payments**: Use wallet balance at checkout
- **Refunds**: Automatic refund credits to wallet
- **Withdrawals**: Customer withdrawal requests with admin approval
- **Admin Tools**: Balance adjustments, transaction management, analytics

### Requirements

- Botble CMS version 7.5.0 or higher
- Ecommerce plugin installed and activated
- At least one payment gateway configured (for top-ups)

### Installation

```bash
# The plugin should be located at:
platform/plugins/e-wallet

# Run migrations
php artisan migrate

# Publish assets
php artisan vendor:publish --tag=cms-public --force
```

### Basic Configuration

1. Navigate to **Admin → Plugins** and activate the E-Wallet plugin
2. Go to **Admin → E-Wallet → Settings** to configure:
   - Enable/disable e-wallet functionality
   - Set minimum and maximum top-up amounts
   - Configure withdrawal settings
   - Set allowed payment methods for top-ups

3. Enable wallet as a payment method:
   - Go to **Admin → Payments → Payment methods**
   - Enable "Wallet" payment method

### Customer Usage

Customers can access their wallet at:
```
/customer/e-wallet
```

From there they can:
- View current balance
- See transaction history
- Top-up their wallet
- Request withdrawals
- Pay with wallet at checkout

### Admin Management

Admins can manage wallets at:
```
/admin/e-wallet
```

Available features:
- View all wallets and balances
- View all transactions
- Manually adjust customer balances
- Manage top-up requests
- Approve/reject withdrawal requests
- View analytics and reports

## Key Features

### 🔐 Security
- Transaction locking to prevent race conditions
- Idempotency keys to prevent duplicate transactions
- Comprehensive audit trail
- Admin permissions system

### 💰 Transactions
- Six transaction types: Top-up, Payment, Refund, Admin Adjustment, Vendor Payout, Withdrawal
- Full transaction history with before/after balances
- Transaction metadata support
- Reference tracking (link to orders, etc.)

### 🔄 Automation
- Automatic refunds to wallet
- Event-driven architecture
- Webhook notifications
- Payment gateway integration

### 📊 Reporting
- Dashboard with key metrics
- Transaction reports
- Top wallets by balance
- Export capabilities

## Next Steps

- Read the [Installation Guide](installation.md) for detailed setup instructions
- Check the [Configuration Guide](configuration.md) for all available settings
- Explore the [Features Documentation](features.md) for in-depth feature explanations
- Review the [API Reference](api.md) for developer integration

## Support

For issues, questions, or feature requests, please contact Botble Technologies support.

## License

This plugin is proprietary software developed by Botble Technologies.
