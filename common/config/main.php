<?php
$config = [
    'name' => 'KODI',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',

    // Path aliases
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],

    // Application components
    'components' => [
        // Database connection
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => strtr('mysql:host={host};port={port};dbname={database}', [
                '{host}' => getenv('KODI_DB_MYSQL_HOSTNAME'),
                '{port}' => getenv('KODI_DB_MYSQL_PORT'),
                '{database}' => getenv('KODI_DB_MYSQL_DATABASE'),
            ]),
            'username' => getenv('KODI_DB_MYSQL_USERNAME'),
            'password' => getenv('KODI_DB_MYSQL_PASSWORD'),
            'charset' => getenv('KODI_DB_MYSQL_CHARSET'),
            'enableSchemaCache' => !YII_DEBUG,
        ],

        // Caching
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],

        // System Settings (stores in DB)
        'settings' => [
            'class' => \kodi\common\models\Setting::class,
        ],

        // System Security
        'security' => [
            'class' => \kodi\common\components\Security::class,
        ],

        // Mailing
        'mailer' => [
            'class' => \yii\swiftmailer\Mailer::class,
            'useFileTransport' => YII_ENV_LOCAL ? true : false,
            'viewPath' => '@kodi/common/mail',
        ],

        'mailchimp' => [
            'class' => \sammaye\yiichimp\Chimp::class,
            'apikey' => getenv('KODI_SERVICE_MAILCHIMP_API_KEY'),
        ],

        // Internationalization
        'i18n' => [
            'class' => \yii\i18n\I18N::class,
            'translations' => [
                'common' => [
                    'class' => \yii\i18n\GettextMessageSource::class,
                    'basePath' => '@common/messages',
                    'catalog' => 'common',
                ],
                'console' => [
                    'class' => \yii\i18n\GettextMessageSource::class,
                    'basePath' => '@common/messages',
                    'catalog' => 'console',
                ],
                'frontend' => [
                    'class' => \yii\i18n\GettextMessageSource::class,
                    'basePath' => '@common/messages',
                    'catalog' => 'frontend',
                ],
                'backend' => [
                    'class' => \yii\i18n\GettextMessageSource::class,
                    'basePath' => '@common/messages',
                    'catalog' => 'backend',
                ],
                'api' => [
                    'class' => \yii\i18n\GettextMessageSource::class,
                    'basePath' => '@common/messages',
                    'catalog' => 'api',
                ],
            ]
        ],

        // Logging
        'log' => [
            'traceLevel' => YII_ENV_LOCAL ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
    ],

    // Custom params
    'params' => require(__DIR__ . '/params.php'),
];

// Configuration adjustments for 'local' environment
if (YII_ENV_LOCAL) {
    // Enable Gii code generator
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
        'allowedIPs' => ['*']
    ];
}

return $config;
