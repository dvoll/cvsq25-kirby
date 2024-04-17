<?php

use dvll\Sitepackage\Menu;

require_once __DIR__ . '/../../vendor/vlucas/phpdotenv/src/Dotenv.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(realpath(__DIR__ . '/../../'));
$dotenv->load();

return [
    'panel' => [
        'language' => 'de',
    ],
    'debug' => json_decode(getenv('KIRBY_DEBUG')),
    'routes' => [],
    'tobimori.seo' => [
        'lang' => 'de_DE',
        'canonicalBase' => getenv("APP_URL"),
        'files' => [
            'parent' => 'site.find("page://images")',
            'template' => 'image'
        ],
    ],
    /** Email */
    'email' => [
        'transport' => [
            'type' => 'smtp',
            'host' => getenv("KIRBY_MAIL_HOST"),
            'port' => json_decode(getenv("KIRBY_MAIL_PORT")),
            'security' => json_decode(getenv('KIRBY_MAIL_SECURITY')),
            'auth' => json_decode(getenv('KIRBY_MAIL_AUTH')),
            'username' => getenv("KIRBY_MAIL_USER"),
            'password' => getenv("KIRBY_MAIL_PASS")
        ]
    ],
    'auth' => [
        'methods' => ['password', 'password-reset'],
        'challenge' => [
            'email' => [
                'from' => getenv("KIRBY_MAIL_FROM"),
                'fromName' => 'CVJM Stift Quernheim e.V.'
            ]
        ]
    ],
    'panel.menu' => fn () => [
        'site' => Menu::site('Website'),
        '-',
        'newsletters' => Menu::page('Newsletter', 'email', page('newsletters')),
        '-',
        'images' => Menu::page('Bilder', 'images', page('page://images')),
        'users',
        '-',
        'system',
    ],
    'ready' => fn () => [
        'panel' => [
            'favicon' => option('debug') ? 'assets/panel/favicon-dev.svg' : 'assets/panel/favicon-live.svg',
        ],
    ]
];
