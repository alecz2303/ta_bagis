<?php

return [
    [
        'key' => 'sales.payment_methods.mpbridge',
        'name' => 'MPBridge',
        'info' => 'Configuración de MPBridge (Mercado Pago por redirección).',
        'sort' => 1,

        'fields' => [
            [
                'name' => 'title',
                'title' => 'Title',
                'type' => 'text',
                'validation' => 'required',
                'channel_based' => false,
                'locale_based' => true,
            ],
            [
                'name' => 'description',
                'title' => 'Description',
                'type' => 'textarea',
                'channel_based' => false,
                'locale_based' => true,
            ],
            [
                'name' => 'active',
                'title' => 'Status',
                'type' => 'boolean',
                'validation' => 'required',
                'channel_based' => false,
                'locale_based' => false,
            ],
            [
                'name' => 'access_token',
                'title' => 'Access Token',
                'type' => 'password',
                'validation' => 'required',
                'channel_based' => false,
                'locale_based' => false,
                'info' => 'Token privado de Mercado Pago.',
            ],
            [
                'name' => 'sandbox',
                'title' => 'Sandbox',
                'type' => 'boolean',
                'validation' => 'required',
                'channel_based' => false,
                'locale_based' => false,
            ],
            [
                'name' => 'sort',
                'title' => 'Sort Order',
                'type' => 'number',
                'validation' => 'required|numeric',
                'channel_based' => false,
                'locale_based' => false,
            ],
            [
                'name' => 'logo',
                'title' => 'Logo',
                'type' => 'image',
                'channel_based' => false,
                'locale_based' => false,
                'info' => 'Imagen para mostrar en checkout.',
            ],
        ],
    ],
];
