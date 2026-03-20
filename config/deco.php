<?php

declare(strict_types=1);

return [
    'dev_name' => env('DECO_DEV_NAME', 'Nacho'),
    'dev_email' => env('DECO_DEV_EMAIL', 'dev@deco.local'),

    /** Dominio por defecto si en el login solo se escribe la parte local (sin @). */
    'login_email_domain' => env('DECO_LOGIN_EMAIL_DOMAIN', 'deco.local'),

    'currency' => env('DECO_CURRENCY', 'ARS'),
    'currency_symbol' => '$',

    /*
    | Carta digital pública (/carta/{slug})
    */
    'menu' => [
        'backgrounds' => [
            'parchment' => 'Pergamino cálido',
            'linen' => 'Lino texturado',
            'marble' => 'Mármol elegante',
            'dark_bistro' => 'Bistró oscuro',
            'cork' => 'Corcho natural',
            'chalk' => 'Pizarra',
            'minimal' => 'Minimal claro',
        ],
        'font_pairs' => [
            'classic' => 'Clásica editorial (serif + sans)',
            'modern' => 'Moderna geométrica',
            'elegant' => 'Elegante fina',
        ],
        'ribbon_presets' => [
            'accent' => 'Acento (tema)',
            'gold' => 'Dorado',
            'emerald' => 'Esmeralda',
            'wine' => 'Vino',
            'slate' => 'Gris pizarra',
        ],
        'dietary_tags' => [
            'vegano' => 'Vegano',
            'vegetariano' => 'Vegetariano',
            'sin_lactosa' => 'Sin lactosa',
            'sin_gluten' => 'Sin gluten',
            'picante' => 'Picante',
            'nuevo' => 'Nuevo',
            'recomendado' => 'Recomendado',
        ],
    ],
];
