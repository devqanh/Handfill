<?php

return [
    [
        'name' => 'Lark Webhook',
        'flag' => 'lark-webhook.index',
    ],
    [
        'name' => 'View',
        'flag' => 'lark-webhook.show',
        'parent_flag' => 'lark-webhook.index',
    ],
    [
        'name' => 'Delete',
        'flag' => 'lark-webhook.destroy',
        'parent_flag' => 'lark-webhook.index',
    ],
    [
        'name' => 'Lark Webhook',
        'flag' => 'lark-webhook.settings',
        'parent_flag' => 'settings.others',
    ],
];
