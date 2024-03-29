<?php

namespace kodi\backend\assets;

use yii\web\AssetBundle;
use yii\web\YiiAsset;

/**
 * Main backend application asset bundle.
 */
class AppAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $basePath = '@webroot';

    /**
     * @inheritdoc
     */
    public $baseUrl = '@web';

    /**
     * @inheritdoc
     */
    public $css = [
        'css/main.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        YiiAsset::class,
    ];
}
