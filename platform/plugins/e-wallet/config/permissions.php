<?php

return [
    [
        'name' => 'E-Wallet',
        'flag' => 'e-wallet.index',
        'parent_flag' => 'plugins.ecommerce',
    ],
    [
        'name' => 'View Wallets',
        'flag' => 'e-wallet.wallets.index',
        'parent_flag' => 'e-wallet.index',
    ],
    [
        'name' => 'Create Wallets',
        'flag' => 'e-wallet.wallets.create',
        'parent_flag' => 'e-wallet.wallets.index',
    ],
    [
        'name' => 'Adjust Balance',
        'flag' => 'e-wallet.wallets.adjust',
        'parent_flag' => 'e-wallet.wallets.index',
    ],
    [
        'name' => 'View Transactions',
        'flag' => 'e-wallet.transactions.index',
        'parent_flag' => 'e-wallet.index',
    ],
    [
        'name' => 'View Top-ups',
        'flag' => 'e-wallet.topups.index',
        'parent_flag' => 'e-wallet.index',
    ],
    [
        'name' => 'Complete Top-ups',
        'flag' => 'e-wallet.topups.complete',
        'parent_flag' => 'e-wallet.topups.index',
    ],
    [
        'name' => 'Cancel Top-ups',
        'flag' => 'e-wallet.topups.cancel',
        'parent_flag' => 'e-wallet.topups.index',
    ],
    [
        'name' => 'View Withdrawals',
        'flag' => 'e-wallet.withdrawals.index',
        'parent_flag' => 'e-wallet.index',
    ],
    [
        'name' => 'Approve Withdrawals',
        'flag' => 'e-wallet.withdrawals.approve',
        'parent_flag' => 'e-wallet.withdrawals.index',
    ],
    [
        'name' => 'Reject Withdrawals',
        'flag' => 'e-wallet.withdrawals.reject',
        'parent_flag' => 'e-wallet.withdrawals.index',
    ],
    [
        'name' => 'View Gift Cards',
        'flag' => 'e-wallet.gift-cards.index',
        'parent_flag' => 'e-wallet.index',
    ],
    [
        'name' => 'Create Gift Cards',
        'flag' => 'e-wallet.gift-cards.create',
        'parent_flag' => 'e-wallet.gift-cards.index',
    ],
    [
        'name' => 'Edit Gift Cards',
        'flag' => 'e-wallet.gift-cards.edit',
        'parent_flag' => 'e-wallet.gift-cards.index',
    ],
    [
        'name' => 'Delete Gift Cards',
        'flag' => 'e-wallet.gift-cards.destroy',
        'parent_flag' => 'e-wallet.gift-cards.index',
    ],
    [
        'name' => 'Cancel Gift Cards',
        'flag' => 'e-wallet.gift-cards.cancel',
        'parent_flag' => 'e-wallet.gift-cards.index',
    ],
    [
        'name' => 'Export Gift Cards',
        'flag' => 'e-wallet.gift-cards.export',
        'parent_flag' => 'e-wallet.gift-cards.index',
    ],
    [
        'name' => 'Settings',
        'flag' => 'e-wallet.settings',
        'parent_flag' => 'e-wallet.index',
    ],
    [
        'name' => 'License',
        'flag' => 'e-wallet.license',
        'parent_flag' => 'e-wallet.index',
    ],
];
