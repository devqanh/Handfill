# Administrator User Guide

Welcome to the E-Wallet Admin Guide! This guide will help you manage customer wallets, handle transactions, and configure the system.

## What Can You Do as an Admin?

As an administrator, you can:
- 👀 View all customer wallets and balances
- 📊 Monitor transactions and activity
- ⚙️ Adjust customer balances
- ✅ Approve or reject withdrawal requests
- 🔧 Configure wallet settings
- 📈 View reports and analytics

---

## Getting Started

### Accessing E-Wallet Admin

1. Log in to your **Admin Panel**
2. Look for **"E-Wallet"** in the left sidebar menu
3. Click to expand and see options:
   - Dashboard
   - Wallets
   - Transactions
   - Top-ups
   - Withdrawals
   - Settings

---

## Dashboard Overview

### What You'll See

The dashboard shows you important metrics at a glance:

**Key Statistics:**
- 💰 **Total Wallets**: Number of customer wallets
- 📊 **Total Balance**: Total money in all wallets
- 📈 **Transactions Today**: Number of transactions today
- 💵 **Credits Today**: Money added to wallets today

**Charts and Reports:**
- Top wallets by balance
- Recent transactions
- Transaction trends

### How to Use the Dashboard

1. Go to **E-Wallet → Dashboard**
2. Review the statistics
3. Click on charts for more details
4. Use **"View All"** links to see full lists

---

## Managing Customer Wallets

### Viewing All Wallets

**Step 1: Access Wallets**
- Go to **E-Wallet → Wallets**

**Step 2: Browse Wallets**
You'll see a table with:
- Customer name
- Email address
- Current balance
- Currency
- Last activity date

**Step 3: Search and Filter**
- Use the search box to find specific customers
- Filter by balance amount
- Sort by any column

### Viewing Wallet Details

**To see a specific wallet:**
1. Click on a customer's name or balance
2. You'll see:
   - Current balance (large display)
   - Customer information
   - Recent transactions
   - Quick actions (Adjust Balance, View Transactions)

### Adjusting Customer Balance

**When to Adjust:**
- Customer service compensation
- Promotional credits
- Fixing errors
- Special circumstances

**How to Adjust:**

**Step 1: Find the Wallet**
- Go to **E-Wallet → Wallets**
- Find the customer
- Click **"Adjust Balance"** button

**Step 2: Choose Adjustment Type**
- **Credit (Add)**: Adds money to wallet
- **Debit (Subtract)**: Removes money from wallet

**Step 3: Enter Amount**
- Type the amount in dollars (e.g., 10.50)
- The system converts to cents automatically

**Step 4: Provide Reason**
- **IMPORTANT**: Always explain why you're adjusting
- Examples:
  - "Customer service compensation for delayed order"
  - "Promotional credit for loyalty program"
  - "Correction for duplicate charge"

**Step 5: Confirm**
- Review all details
- Click **"Adjust Balance"**
- You'll see a success message

**What Happens:**
- Balance is updated immediately
- Transaction is recorded with your admin ID
- Customer can see the transaction in their history

### Balance Adjustment Tips

✅ **DO:**
- Always provide a clear reason
- Double-check the amount
- Verify you have the right customer
- Document in customer notes

❌ **DON'T:**
- Adjust without a valid reason
- Use vague descriptions
- Forget to notify the customer
- Make adjustments without authorization

---

## Managing Transactions

### Viewing All Transactions

**Step 1: Access Transactions**
- Go to **E-Wallet → Transactions**

**Step 2: Browse Transactions**
You'll see:
- Date and time
- Customer name
- Transaction type
- Amount
- Balance before/after
- Status
- Description

**Step 3: Filter Transactions**
- By date range
- By transaction type
- By customer
- By status
- By amount range

### Understanding Transaction Types

| Type | Icon | What It Means |
|------|------|---------------|
| Top Up | 💰 | Customer added money |
| Payment | 🛒 | Customer paid for order |
| Refund | 💵 | Order refund to wallet |
| Admin Adjustment | ⚙️ | You adjusted balance |
| Vendor Payout | 💼 | Payment to vendor |
| Withdrawal | 💸 | Customer withdrew money |

### Viewing Transaction Details

**To see more information:**
1. Click on any transaction
2. You'll see:
   - Full transaction details
   - Customer information
   - Related order (if applicable)
   - Metadata and notes
   - Balance before and after

---

## Managing Top-ups

### What Are Top-ups?

Top-ups are when customers add money to their wallets using payment gateways (credit cards, PayPal, etc.).

### Viewing Top-up Requests

**Step 1: Access Top-ups**
- Go to **E-Wallet → Top-ups**

**Step 2: Review List**
You'll see:
- Top-up code
- Customer name
- Amount
- Payment method
- Status
- Date

### Top-up Statuses

| Status | What It Means | What You Can Do |
|--------|---------------|-----------------|
| Pending | Waiting for payment | Complete or Cancel |
| Processing | Payment being processed | Wait |
| Completed | Payment successful, wallet credited | View only |
| Failed | Payment failed | View only |
| Cancelled | Top-up cancelled | View only |

### Completing a Top-up Manually

**When to do this:**
- Payment gateway webhook failed
- Customer paid but wallet not credited
- Manual payment received

**How to complete:**
1. Find the pending top-up
2. Click **"Complete"** button
3. Confirm the action
4. Wallet is credited immediately

### Cancelling a Top-up

**When to do this:**
- Customer requested cancellation
- Suspicious activity detected
- Payment cannot be processed

**How to cancel:**
1. Find the pending top-up
2. Click **"Cancel"** button
3. Confirm the action
4. Top-up is marked as cancelled

---

## Managing Withdrawals

### What Are Withdrawals?

Withdrawals are requests from customers to transfer money from their wallet to their bank account or PayPal.

### Viewing Withdrawal Requests

**Step 1: Access Withdrawals**
- Go to **E-Wallet → Withdrawals**

**Step 2: Review Requests**
You'll see:
- Customer name
- Amount
- Payment method (Bank Transfer, PayPal, etc.)
- Status
- Request date

### Withdrawal Statuses

| Status | What It Means | Action Needed |
|--------|---------------|---------------|
| Pending | Awaiting your review | Approve or Reject |
| Processing | Approved, payment being sent | Process payment |
| Completed | Money sent to customer | None |
| Rejected | Request denied | None |

### Reviewing a Withdrawal Request

**Step 1: Open Request**
- Click on the withdrawal to see details

**Step 2: Review Information**
Check:
- ✅ Customer has sufficient balance
- ✅ Payment details are complete
- ✅ No suspicious activity
- ✅ Customer identity verified (for large amounts)

**Step 3: Check Payment Details**

**For Bank Transfer:**
- Bank name
- Account number
- Account holder name
- Routing number
- SWIFT/BIC code (international)

**For PayPal:**
- PayPal email address
- Verify it matches customer email

### Approving a Withdrawal

**Step 1: Verify Everything**
- Confirm all details are correct
- Check customer account is in good standing
- Verify no fraud alerts

**Step 2: Approve**
1. Click **"Approve"** button
2. Confirm the action
3. Status changes to "Processing"

**Step 3: Process Payment**
- Send money via bank transfer or PayPal
- Enter transaction ID in the system
- Mark as "Completed" when done

### Rejecting a Withdrawal

**When to reject:**
- Incorrect payment details
- Suspicious activity
- Customer account issues
- Insufficient verification

**How to reject:**
1. Click **"Reject"** button
2. **IMPORTANT**: Add a note explaining why
3. Confirm the action
4. Money is automatically refunded to wallet
5. Customer receives notification

### Withdrawal Processing Tips

✅ **DO:**
- Process within 1-3 business days
- Verify large amounts carefully
- Keep records of all transactions
- Communicate with customers
- Add notes for rejected requests

❌ **DON'T:**
- Approve without verification
- Process to unverified accounts
- Ignore suspicious patterns
- Forget to update status

---

## Configuring Settings

### Accessing Settings

- Go to **E-Wallet → Settings**

### General Settings

**Enable E-Wallet**
- ☑️ Checked: Wallet is active
- ☐ Unchecked: Wallet is disabled

**Allow Negative Balance**
- ☑️ Checked: Customers can go below zero
- ☐ Unchecked: Balance must stay positive
- **Recommendation**: Keep unchecked unless you have credit programs

### Top-up Settings

**Enable Top-up**
- ☑️ Checked: Customers can add money
- ☐ Unchecked: Top-up is disabled

**Minimum Top-up Amount**
- Lowest amount customers can add
- Example: $10
- **Tip**: Set high enough to avoid micro-transactions

**Maximum Top-up Amount**
- Highest amount customers can add
- Example: $10,000
- **Tip**: Set based on your risk tolerance

**Allowed Payment Methods**
- Select which payment gateways customers can use
- Leave empty to allow all enabled methods
- **Tip**: Only enable trusted gateways

### Withdrawal Settings

**Enable Withdrawal**
- ☑️ Checked: Customers can withdraw
- ☐ Unchecked: Withdrawal is disabled

**Minimum Withdrawal Amount**
- Lowest amount customers can withdraw
- Example: $10
- **Tip**: Set to cover transaction costs

**Maximum Withdrawal Amount**
- Highest amount customers can withdraw
- Example: $5,000
- **Tip**: Set based on fraud prevention needs

**Payout Methods**
- Select available withdrawal methods:
  - Bank Transfer
  - PayPal
  - Other
- **Tip**: Enable methods you can process

### Webhook Settings

**Enable Webhooks**
- ☑️ Checked: Send webhook notifications
- ☐ Unchecked: Webhooks disabled

**Webhook URLs**
- Configure URLs for different events
- Used for integrations with other systems
- **Note**: This is advanced - consult with developers

### Saving Settings

**Important:**
1. Review all changes carefully
2. Click **"Save Settings"** at the bottom
3. Wait for confirmation message
4. Test the changes

---

## Reports and Analytics

### Generating Reports

**Available Reports:**
- Transaction summary by date
- Top customers by wallet balance
- Top-up trends
- Withdrawal patterns
- Revenue from wallet payments

**How to Generate:**
1. Go to **E-Wallet → Dashboard**
2. Select date range
3. Choose report type
4. Click **"Export"** for CSV/PDF

### Monitoring Activity

**Daily Checks:**
- Review pending withdrawals
- Check for unusual transactions
- Monitor top-up failures
- Review balance adjustments

**Weekly Reviews:**
- Analyze transaction trends
- Review top wallet balances
- Check withdrawal approval times
- Assess customer adoption

**Monthly Reports:**
- Total wallet usage
- Revenue from wallet payments
- Customer engagement metrics
- Fraud prevention statistics

---

## Common Admin Tasks

### Task 1: Customer Says Top-up Didn't Work

**Steps:**
1. Go to **E-Wallet → Top-ups**
2. Search for customer
3. Check top-up status
4. If "Pending" and payment confirmed → Click "Complete"
5. If "Failed" → Check payment gateway logs
6. If not found → Ask customer for payment confirmation

### Task 2: Customer Wants Refund

**Steps:**
1. Process order refund normally
2. Money automatically goes to wallet
3. Customer can use it or request withdrawal
4. Verify transaction appears in wallet history

### Task 3: Customer Reports Wrong Balance

**Steps:**
1. Go to **E-Wallet → Wallets**
2. Find customer wallet
3. Review transaction history
4. Check for:
   - Failed transactions
   - Duplicate charges
   - Missing refunds
5. Adjust balance if error confirmed
6. Document the issue

### Task 4: Suspicious Activity Detected

**Steps:**
1. Review customer's transaction history
2. Look for patterns:
   - Multiple rapid top-ups
   - Large unusual amounts
   - Frequent withdrawals
3. Check customer account details
4. If suspicious:
   - Put withdrawal requests on hold
   - Contact customer for verification
   - Report to security team if needed

### Task 5: Customer Can't Withdraw

**Steps:**
1. Check if withdrawal is enabled in settings
2. Verify customer has sufficient balance
3. Check withdrawal limits (min/max)
4. Review customer's withdrawal history
5. Check for any account restrictions
6. Assist customer with correct details

---

## Best Practices

### Security

🔒 **Protect Customer Data**
- Never share wallet balances publicly
- Verify identity for large adjustments
- Monitor for fraud patterns
- Keep audit logs

🔐 **Access Control**
- Only authorized staff should access wallets
- Use strong admin passwords
- Log out when finished
- Review admin activity logs

### Customer Service

😊 **Be Helpful**
- Respond to wallet issues quickly
- Explain adjustments clearly
- Be patient with non-technical customers
- Document all interactions

📧 **Communication**
- Notify customers of balance adjustments
- Explain rejection reasons clearly
- Send confirmation for withdrawals
- Keep customers informed

### Efficiency

⚡ **Process Quickly**
- Review withdrawals daily
- Complete pending top-ups promptly
- Respond to issues within 24 hours
- Automate where possible

📊 **Stay Organized**
- Use filters to find transactions
- Keep notes on unusual cases
- Export reports regularly
- Review metrics weekly

---

## Troubleshooting

### Settings Won't Save

**Try:**
1. Check your admin permissions
2. Clear browser cache
3. Try a different browser
4. Contact technical support

### Can't See Customer Wallet

**Check:**
1. Customer has an account
2. You have correct permissions
3. Search by email, not name
4. Wallet might not be created yet

### Balance Adjustment Failed

**Verify:**
1. Amount is correct format
2. Reason is provided
3. You have permission
4. Customer wallet exists

### Withdrawal Approval Not Working

**Check:**
1. Withdrawal is in "Pending" status
2. You have approval permission
3. Customer has sufficient balance
4. No system errors in logs

---

## Getting Help

### Support Resources

**Technical Support:**
- Email: tech-support@yourcompany.com
- Phone: 1-800-XXX-XXXX (ext. 2)
- Internal Wiki: [link]

**Training:**
- Video tutorials: [link]
- Admin handbook: [link]
- Weekly Q&A sessions

**Escalation:**
- For fraud: Contact security team immediately
- For technical issues: Submit support ticket
- For policy questions: Contact supervisor

---

## Quick Reference

### Important Admin Links

- Dashboard: `/admin/e-wallet`
- Wallets: `/admin/e-wallet/wallets`
- Transactions: `/admin/e-wallet/transactions`
- Top-ups: `/admin/e-wallet/topups`
- Withdrawals: `/admin/e-wallet/withdrawals`
- Settings: `/admin/e-wallet/settings`

### Quick Actions Table

| I Need To... | Where To Go | What To Click |
|--------------|-------------|---------------|
| View all wallets | E-Wallet → Wallets | - |
| Adjust balance | E-Wallet → Wallets | Find customer → Adjust Balance |
| Approve withdrawal | E-Wallet → Withdrawals | Find request → Approve |
| Complete top-up | E-Wallet → Top-ups | Find top-up → Complete |
| View transactions | E-Wallet → Transactions | - |
| Change settings | E-Wallet → Settings | Edit → Save |
| See reports | E-Wallet → Dashboard | View charts |

### Permission Checklist

Make sure you have these permissions:
- ☐ View wallets
- ☐ Adjust balances
- ☐ View transactions
- ☐ Manage top-ups
- ☐ Approve withdrawals
- ☐ Access settings

---

## Summary

As an E-Wallet administrator, you play a crucial role in:

✅ Keeping customer wallets secure  
✅ Processing requests promptly  
✅ Maintaining accurate records  
✅ Providing excellent service  
✅ Monitoring for fraud  

**Remember**: Always verify, document, and communicate!

---

*For technical documentation, see the [main documentation](../README.md).*
