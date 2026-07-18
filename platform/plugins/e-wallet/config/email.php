<?php

return [
    'name' => 'plugins/e-wallet::email.name',
    'description' => 'plugins/e-wallet::email.description',
    'templates' => [
        'topup_completed' => [
            'title' => 'plugins/e-wallet::email.topup_completed_title',
            'description' => 'plugins/e-wallet::email.topup_completed_description',
            'subject' => 'plugins/e-wallet::email.topup_completed_subject',
            'can_off' => true,
            'variables' => [
                'customer_name' => 'plugins/e-wallet::email.customer_name',
                'topup_code' => 'plugins/e-wallet::email.topup_code',
                'topup_amount' => 'plugins/e-wallet::email.topup_amount',
                'wallet_balance' => 'plugins/e-wallet::email.wallet_balance',
                'payment_method' => 'plugins/e-wallet::email.payment_method',
            ],
        ],
        'topup_failed' => [
            'title' => 'plugins/e-wallet::email.topup_failed_title',
            'description' => 'plugins/e-wallet::email.topup_failed_description',
            'subject' => 'plugins/e-wallet::email.topup_failed_subject',
            'can_off' => true,
            'variables' => [
                'customer_name' => 'plugins/e-wallet::email.customer_name',
                'topup_code' => 'plugins/e-wallet::email.topup_code',
                'topup_amount' => 'plugins/e-wallet::email.topup_amount',
            ],
        ],
        'withdrawal_request_admin' => [
            'title' => 'plugins/e-wallet::email.withdrawal_request_admin_title',
            'description' => 'plugins/e-wallet::email.withdrawal_request_admin_description',
            'subject' => 'plugins/e-wallet::email.withdrawal_request_admin_subject',
            'can_off' => true,
            'variables' => [
                'customer_name' => 'plugins/e-wallet::email.customer_name',
                'customer_email' => 'plugins/e-wallet::email.customer_email',
                'withdrawal_amount' => 'plugins/e-wallet::email.withdrawal_amount',
                'payment_method' => 'plugins/e-wallet::email.payment_method',
                'bank_info' => 'plugins/e-wallet::email.bank_info',
                'withdrawal_link' => 'plugins/e-wallet::email.withdrawal_link',
            ],
        ],
        'withdrawal_approved' => [
            'title' => 'plugins/e-wallet::email.withdrawal_approved_title',
            'description' => 'plugins/e-wallet::email.withdrawal_approved_description',
            'subject' => 'plugins/e-wallet::email.withdrawal_approved_subject',
            'can_off' => true,
            'variables' => [
                'customer_name' => 'plugins/e-wallet::email.customer_name',
                'withdrawal_amount' => 'plugins/e-wallet::email.withdrawal_amount',
                'payment_method' => 'plugins/e-wallet::email.payment_method',
                'wallet_balance' => 'plugins/e-wallet::email.wallet_balance',
            ],
        ],
        'withdrawal_rejected' => [
            'title' => 'plugins/e-wallet::email.withdrawal_rejected_title',
            'description' => 'plugins/e-wallet::email.withdrawal_rejected_description',
            'subject' => 'plugins/e-wallet::email.withdrawal_rejected_subject',
            'can_off' => true,
            'variables' => [
                'customer_name' => 'plugins/e-wallet::email.customer_name',
                'withdrawal_amount' => 'plugins/e-wallet::email.withdrawal_amount',
                'rejection_reason' => 'plugins/e-wallet::email.rejection_reason',
                'wallet_balance' => 'plugins/e-wallet::email.wallet_balance',
            ],
        ],
        'balance_adjusted' => [
            'title' => 'plugins/e-wallet::email.balance_adjusted_title',
            'description' => 'plugins/e-wallet::email.balance_adjusted_description',
            'subject' => 'plugins/e-wallet::email.balance_adjusted_subject',
            'can_off' => true,
            'variables' => [
                'customer_name' => 'plugins/e-wallet::email.customer_name',
                'adjustment_type' => 'plugins/e-wallet::email.adjustment_type',
                'adjustment_amount' => 'plugins/e-wallet::email.adjustment_amount',
                'adjustment_reason' => 'plugins/e-wallet::email.adjustment_reason',
                'wallet_balance' => 'plugins/e-wallet::email.wallet_balance',
            ],
        ],
        'gift_card_shared' => [
            'title' => 'plugins/e-wallet::email.gift_card_shared_title',
            'description' => 'plugins/e-wallet::email.gift_card_shared_description',
            'subject' => 'plugins/e-wallet::email.gift_card_shared_subject',
            'can_off' => true,
            'variables' => [
                'recipient_name' => 'plugins/e-wallet::email.recipient_name',
                'sender_name' => 'plugins/e-wallet::email.sender_name',
                'gift_card_code' => 'plugins/e-wallet::email.gift_card_code',
                'gift_card_value' => 'plugins/e-wallet::email.gift_card_value',
                'gift_card_expiry' => 'plugins/e-wallet::email.gift_card_expiry',
                'gift_message' => 'plugins/e-wallet::email.gift_message',
                'redeem_url' => 'plugins/e-wallet::email.redeem_url',
            ],
        ],
    ],
];
