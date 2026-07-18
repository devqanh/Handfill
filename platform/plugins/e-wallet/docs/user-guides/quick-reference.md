# Quick Reference Guide

Fast reference for common E-Wallet tasks and information.

---

## For Customers

### Quick Actions

| I Want To... | Go Here | Do This |
|--------------|---------|---------|
| Check my balance | My Wallet | View balance at top |
| Add money | My Wallet | Click "Top Up Wallet" |
| Withdraw money | My Wallet | Click "Withdraw" |
| See transactions | My Wallet | Scroll to Transaction History |
| Pay with wallet | Checkout | Select "Wallet" payment |
| Get help | Contact Support | Email/Phone/Chat |

### Important Links

- **My Wallet**: `/customer/e-wallet`
- **Top Up**: `/customer/e-wallet/topup`
- **Withdrawals**: `/customer/e-wallet/withdrawals`
- **My Account**: `/customer/overview`

### Transaction Types

| Icon | Type | Balance |
|------|------|---------|
| 💰 | Top Up | Increases ⬆️ |
| 🛒 | Payment | Decreases ⬇️ |
| 💵 | Refund | Increases ⬆️ |
| 💸 | Withdrawal | Decreases ⬇️ |

### Top-up Steps

1. Click "Top Up Wallet"
2. Choose/Enter amount
3. Select payment method
4. Complete payment
5. Done! ✅

### Withdrawal Steps

1. Click "Withdraw"
2. Enter amount
3. Choose payout method
4. Provide payment details
5. Submit request
6. Wait for approval (1-3 days)
7. Receive money (3-5 days)

### Payment Steps

1. Add items to cart
2. Go to checkout
3. Select "Wallet"
4. Place order
5. Done! ✅

### Limits

| Action | Typical Minimum | Typical Maximum |
|--------|----------------|-----------------|
| Top-up | $10 | $10,000 |
| Withdrawal | $10 | $5,000 |
| Payment | $0.01 | Your balance |

*Check your wallet page for exact limits*

### Processing Times

| Action | Time |
|--------|------|
| Top-up | Instant |
| Payment | Instant |
| Refund | 1-3 business days |
| Withdrawal Review | 1-3 business days |
| Bank Transfer | 3-5 business days |
| PayPal | 1-2 business days |

### Support Contacts

- **Email**: support@yourstore.com
- **Phone**: 1-800-XXX-XXXX
- **Hours**: Mon-Fri 9AM-5PM

---

## For Administrators

### Quick Actions

| I Need To... | Go Here | Do This |
|--------------|---------|---------|
| View all wallets | E-Wallet → Wallets | Browse list |
| Adjust balance | E-Wallet → Wallets | Find customer → Adjust Balance |
| Approve withdrawal | E-Wallet → Withdrawals | Find request → Approve |
| Reject withdrawal | E-Wallet → Withdrawals | Find request → Reject |
| Complete top-up | E-Wallet → Top-ups | Find top-up → Complete |
| View transactions | E-Wallet → Transactions | Browse/Filter |
| Change settings | E-Wallet → Settings | Edit → Save |
| View reports | E-Wallet → Dashboard | View charts/Export |

### Admin Links

- **Dashboard**: `/admin/e-wallet`
- **Wallets**: `/admin/e-wallet/wallets`
- **Transactions**: `/admin/e-wallet/transactions`
- **Top-ups**: `/admin/e-wallet/topups`
- **Withdrawals**: `/admin/e-wallet/withdrawals`
- **Settings**: `/admin/e-wallet/settings`

### Balance Adjustment

**Credit (Add Money):**
1. Find customer wallet
2. Click "Adjust Balance"
3. Select "Credit (Add)"
4. Enter amount
5. Provide reason
6. Confirm

**Debit (Remove Money):**
1. Find customer wallet
2. Click "Adjust Balance"
3. Select "Debit (Subtract)"
4. Enter amount
5. Provide reason
6. Confirm

### Withdrawal Approval

**To Approve:**
1. Review request details
2. Verify payment information
3. Check for suspicious activity
4. Click "Approve"
5. Process actual payment
6. Update status to "Completed"

**To Reject:**
1. Review request
2. Click "Reject"
3. Add reason for rejection
4. Confirm
5. Money auto-refunds to wallet

### Transaction Statuses

| Status | Meaning | Action |
|--------|---------|--------|
| Pending | Waiting | Review/Process |
| Processing | In progress | Monitor |
| Completed | Finished | None |
| Failed | Error occurred | Investigate |
| Cancelled | Cancelled | None |

### Settings Checklist

- ☐ Enable E-Wallet
- ☐ Set top-up min/max
- ☐ Set withdrawal min/max
- ☐ Select payment methods
- ☐ Select payout methods
- ☐ Configure webhooks (optional)
- ☐ Save settings

### Daily Tasks

- ✅ Review pending withdrawals
- ✅ Check failed top-ups
- ✅ Monitor unusual activity
- ✅ Respond to customer issues

### Weekly Tasks

- ✅ Review transaction trends
- ✅ Check top wallet balances
- ✅ Analyze approval times
- ✅ Generate reports

### Monthly Tasks

- ✅ Comprehensive reports
- ✅ Revenue analysis
- ✅ Customer adoption metrics
- ✅ Security review

### Required Permissions

- ☐ `e-wallet.index` - Access menu
- ☐ `e-wallet.wallets.index` - View wallets
- ☐ `e-wallet.wallets.adjust` - Adjust balances
- ☐ `e-wallet.transactions.index` - View transactions
- ☐ `e-wallet.topups.index` - View top-ups
- ☐ `e-wallet.topups.complete` - Complete top-ups
- ☐ `e-wallet.topups.cancel` - Cancel top-ups
- ☐ `e-wallet.withdrawals.index` - View withdrawals
- ☐ `e-wallet.withdrawals.approve` - Approve withdrawals
- ☐ `e-wallet.withdrawals.reject` - Reject withdrawals
- ☐ `e-wallet.settings` - Manage settings

### Common Issues & Quick Fixes

| Issue | Quick Fix |
|-------|-----------|
| Settings won't save | Clear cache, check permissions |
| Can't find wallet | Search by email, check if created |
| Top-up stuck | Check payment, manually complete |
| Withdrawal won't approve | Check status, verify permissions |
| Balance incorrect | Review transactions, contact support |

### Support Contacts

- **Technical**: tech-support@yourcompany.com
- **Admin Help**: admin-support@yourcompany.com
- **Emergency**: 1-800-XXX-XXXX (ext. 911)

---

## Keyboard Shortcuts

*If available in your system*

| Action | Shortcut |
|--------|----------|
| Search | Ctrl/Cmd + K |
| Refresh | F5 |
| Go to Dashboard | Alt + D |
| Go to Wallets | Alt + W |
| Go to Transactions | Alt + T |

---

## Status Icons

| Icon | Meaning |
|------|---------|
| ✅ | Completed/Approved |
| ⏳ | Pending/Waiting |
| ⚙️ | Processing |
| ❌ | Failed/Rejected |
| 🚫 | Cancelled |
| ⚠️ | Warning/Review Needed |

---

## Important Numbers

### Customer Limits
- Min Top-up: **$10** (typical)
- Max Top-up: **$10,000** (typical)
- Min Withdrawal: **$10** (typical)
- Max Withdrawal: **$5,000** (typical)

*Check settings for exact values*

### Processing Times
- Top-up: **Instant**
- Payment: **Instant**
- Withdrawal Review: **1-3 business days**
- Bank Transfer: **3-5 business days**
- PayPal: **1-2 business days**

---

## Emergency Contacts

### For Customers
**Suspicious Activity**: Change password immediately, contact support

### For Admins
**Fraud Alert**: Contact security team immediately  
**System Down**: Contact technical support  
**Policy Question**: Contact supervisor

---

## Useful Tips

### For Customers
💡 Add money before sales  
💡 Check balance before shopping  
💡 Keep confirmation emails  
💡 Use strong passwords  
💡 Enable two-factor authentication

### For Admins
💡 Always document adjustments  
💡 Verify large withdrawals  
💡 Process requests promptly  
💡 Monitor for patterns  
💡 Keep audit logs

---

## Related Documentation

- [Customer Guide](customer-guide.md) - Full customer manual
- [Admin Guide](admin-guide.md) - Full admin manual
- [FAQ](faq.md) - Frequently asked questions
- [Technical Docs](../README.md) - Developer documentation

---

*Print this page for quick reference at your desk!*

*Last Updated: December 22, 2025*
