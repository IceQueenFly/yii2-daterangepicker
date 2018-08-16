<?php

namespace icequeen\yii\widgets;

use yii\web\AssetBundle;
use yii\web\View;
use yii\bootstrap\BootstrapAsset;
use yii\web\AssetBundle;
/**
 * Main backend application asset bundle.
 */
class DateAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $jsOptions = ['position' => View::POS_HEAD];
    /**
     * @inheritdoc
     */
    public $depends = [
        JqueryAsset::class,
        BootstrapAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . 'assets';
        $this->js = [
            'js/moment.min.js',
            'js/daterangepicker.js',
        ];
        $this->css = [
            'css/daterangepicker.css',
        ];
        parent::init();
    }
}
