<?php

return [
    'mpbridge' => [
        'code' => 'mpbridge',
        'title' => 'Mercado Pago', // <- IMPORTANTE (esto evita Undefined title)
        'description' => 'Pago por redirección con confirmación por webhook.',
        'class' => \Webkul\MPBridge\Payment\MPBridge::class,
        'active' => true,
        'sort' => 6,
    ],
];
