<?php

/**
 * Declares frontend application configuration.
 */

return [
    'id' => 'kodi-frontend',
    'name' => 'KODI',
    'language' => 'en',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'kodi\frontend\controllers',
    'bootstrap' => ['log'],
    'defaultRoute' => 'site/plus',
    'components' => [
        'request' => [
            'class' => \yii\web\Request::class,
            'cookieValidationKey' => getenv('KODI_MODULE_FRONTEND_COOKIE_VALIDATION_KEY'),
            'enableCookieValidation' => true,
            'csrfParam' => '_csrf-frontend',
        ],
        'user' => [
            'identityClass' => kodi\common\models\user\User::class,
            'enableAutoLogin' => true,
            'loginUrl' => ['/auth/sign-in'],
            'identityCookie' => ['name' => '_identity-frontend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'advanced-frontend',
            'timeout' => 60 * 60 * 2, // 2 hours
        ],

        // Asset manager
        'assetManager' => [
            'class' => \yii\web\AssetManager::class,
            'linkAssets' => true,
            'bundles' => [
                \yii\bootstrap\BootstrapAsset::class => false,
                \yii\bootstrap\BootstrapPluginAsset::class => false,
            ],
        ],

        'errorHandler' => [
            'errorAction' => 'site/error',
        ],

        'urlManager' => [
            'class' => \codemix\localeurls\UrlManager::class,
            'languages' => ['en', 'it'],
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                [
                    'class' => \kodi\frontend\routes\SiteRule::class,
                    'pattern' => '<slug:[\w-]+>',
                    'route' => 'page/view',
                ],
                'auth/activate/<token:[\w-=]+>' => 'auth/activate',
                'auth/password-reset/<token:[\w-=]+>' => 'auth/password-reset',
                'about' => 'site/about',
                'order' => 'order',
                '<slug:[\w-]+>' => 'site/view',
            ],
        ],
    ],

    // Custom params
    'params' => require(__DIR__ . '/params.php'),
];
