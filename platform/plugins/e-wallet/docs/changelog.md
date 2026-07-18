# Changelog

All notable changes to the E-Wallet plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-12-22

### Added

#### Core Features
- **Wallet Management**: Automatic wallet creation for customers with balance tracking
- **Transaction System**: Six transaction types (Top-up, Payment, Refund, Admin Adjustment, Vendor Payout, Withdrawal)
- **Top-up System**: Customer-initiated wallet top-ups via payment gateways
- **Withdrawal System**: Customer withdrawal requests with admin approval workflow
- **Payment Integration**: Wallet as a payment method at checkout
- **Refund Management**: Automatic refund credits to wallet

#### Admin Features
- Dashboard with wallet statistics and analytics
- Wallet listing and management
- Transaction history viewing and filtering
- Manual balance adjustments (credit/debit)
- Top-up management (complete/cancel)
- Withdrawal approval/rejection workflow
- Comprehensive permissions system
- Settings configuration page

#### Customer Features
- My Wallet page with balance display
- Transaction history viewing
- Top-up wallet functionality
- Withdrawal request submission
- Pay with wallet at checkout
- View order-related transactions

#### Developer Features
- Event system (WalletCredited, WalletDebited, WalletTransactionCreated)
- Webhook support for top-up events
- Exception handling (InsufficientBalanceException, DuplicateTransactionException, WalletNotFoundException)
- Service-oriented architecture
- Idempotency key support
- Comprehensive test coverage (14 feature tests)
- Helper functions for settings management

#### Configuration
- Enable/disable e-wallet functionality
- Allow negative balance option
- Top-up min/max amount settings
- Withdrawal min/max amount settings
- Allowed payment methods for top-ups
- Payout methods for withdrawals
- Webhook URLs configuration

#### Security
- Transaction locking to prevent race conditions
- Idempotency keys to prevent duplicate transactions
- Comprehensive audit trail
- Role-based permissions
- Balance before/after tracking

#### Database
- `ec_wallets` table for wallet storage
- `ec_wallet_transactions` table for transaction history
- `ec_wallet_topups` table for top-up requests
- `ec_wallet_withdrawals` table for withdrawal requests
- Proper indexes for performance
- Foreign key constraints

#### Documentation
- Complete README with quick start guide
- Installation guide with step-by-step instructions
- Configuration guide for all settings
- Features documentation with examples
- API reference for developers
- Webhooks documentation with payloads
- Troubleshooting guide for common issues
- Changelog for version tracking

### Technical Details

#### Requirements
- Botble CMS 7.5.0 or higher
- Ecommerce plugin installed and activated
- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+

#### Dependencies
- `botble/ecommerce` - Required for order integration

#### Migrations
- `2025_12_19_000001_create_wallets_table.php`
- `2025_12_19_000002_create_wallet_transactions_table.php`
- `2025_12_19_000003_create_wallet_topups_table.php`
- `2025_12_21_000001_create_wallet_withdrawals_table.php`

#### Routes
- Admin routes: `/admin/e-wallet/*`
- Customer routes: `/customer/e-wallet/*`

#### Permissions
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

### Known Issues

None at this time.

### Upgrade Notes

This is the initial release. No upgrade steps required.

---

## Future Releases

### Planned Features

See [analysis.md](../../../.gemini/antigravity/brain/7c1db448-689f-45d1-b09f-3277d641f63c/analysis.md) for detailed improvement suggestions including:

- Wallet-to-wallet transfers (P2P payments)
- Recurring top-ups / auto-reload
- Wallet rewards & loyalty points
- Transaction limits & velocity checks
- Wallet statements & exports
- Multi-wallet support
- Scheduled withdrawals
- Wallet freeze/lock functionality
- Transaction disputes
- QR code payments
- Enhanced analytics dashboard
- Email/SMS notifications
- Two-factor authentication
- API endpoints for integrations

---

## Version History

### [1.0.0] - 2025-12-22
- Initial release

---

## Support

For issues, questions, or feature requests, please contact Botble Technologies support.

## License

This plugin is proprietary software developed by Botble Technologies.
