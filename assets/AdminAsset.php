<?php

namespace asb\yii2\modules\content_2_170309\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class AdminAsset extends AssetBundle
{
    public $css = [
        'content-admin.css',
    ];

    //public $js = [];
    //public $jsOptions = ['position' => View::POS_BEGIN];

    public $depends = [
        'yii\bootstrap\BootstrapAsset', // add only CSS - need to move up 'bootstrap.css' in <head>s of render HTML-results
    ];

    public function init()
    {
        parent::init();
        $this->sourcePath = __DIR__ . '/admin';
    }
}
