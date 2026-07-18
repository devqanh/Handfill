<?php

return [
    'name' => 'E-Wallet',
    'description' => 'Email notifications for E-Wallet transactions and activities',

    'customer_name' => 'Customer name',
    'customer_email' => 'Customer email',
    'topup_code' => 'Top-up code',
    'topup_amount' => 'Top-up amount',
    'wallet_balance' => 'Current wallet balance',
    'payment_method' => 'Payment method',
    'withdrawal_amount' => 'Withdrawal amount',
    'bank_info' => 'Bank information',
    'withdrawal_link' => 'Link to view withdrawal request',
    'rejection_reason' => 'Rejection reason',
    'adjustment_type' => 'Adjustment type (Credit/Debit)',
    'adjustment_amount' => 'Adjustment amount',
    'adjustment_reason' => 'Adjustment reason',

    'topup_completed_title' => 'Top-up Completed',
    'topup_completed_description' => 'Send email to customer when their wallet top-up is completed',
    'topup_completed_subject' => 'Your wallet top-up has been completed',

    'topup_failed_title' => 'Top-up Failed',
    'topup_failed_description' => 'Send email to customer when their wallet top-up fails',
    'topup_failed_subject' => 'Your wallet top-up could not be processed',

    'withdrawal_request_admin_title' => 'New Withdrawal Request (Admin)',
    'withdrawal_request_admin_description' => 'Send email to admin when a customer submits a withdrawal request',
    'withdrawal_request_admin_subject' => 'New wallet withdrawal request from {{ customer_name }}',

    'withdrawal_approved_title' => 'Withdrawal Approved',
    'withdrawal_approved_description' => 'Send email to customer when their withdrawal request is approved',
    'withdrawal_approved_subject' => 'Your withdrawal request has been approved',

    'withdrawal_rejected_title' => 'Withdrawal Rejected',
    'withdrawal_rejected_description' => 'Send email to customer when their withdrawal request is rejected',
    'withdrawal_rejected_subject' => 'Your withdrawal request has been rejected',

    'balance_adjusted_title' => 'Balance Adjusted',
    'balance_adjusted_description' => 'Send email to customer when their wallet balance is adjusted by admin',
    'balance_adjusted_subject' => 'Your wallet balance has been adjusted',

    'recipient_name' => 'Recipient name',
    'sender_name' => 'Sender name',
    'gift_card_code' => 'Gift card code',
    'gift_card_value' => 'Gift card value',
    'gift_card_expiry' => 'Gift card expiry date',
    'gift_message' => 'Gift message',
    'redeem_url' => 'URL to check gift card balance',

    'gift_card_shared_title' => 'Gift Card Shared',
    'gift_card_shared_description' => 'Send email to recipient when a gift card is purchased for them',
    'gift_card_shared_subject' => 'You have received a gift card from {{ sender_name }}',
];
