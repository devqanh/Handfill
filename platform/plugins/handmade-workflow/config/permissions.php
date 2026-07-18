<?php

return [
    [
        'name' => 'Handmade production workflow',
        'flag' => 'handmade-workflow.index',
        'parent_flag' => 'plugins.ecommerce',
    ],
    [
        'name' => 'Update production status',
        'flag' => 'handmade-workflow.update-status',
        'parent_flag' => 'handmade-workflow.index',
    ],
    [
        'name' => 'Quote orders',
        'flag' => 'handmade-workflow.quote',
        'parent_flag' => 'handmade-workflow.index',
    ],
];
