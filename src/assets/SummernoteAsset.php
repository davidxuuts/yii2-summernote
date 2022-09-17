<?php

namespace davidxu\summernote\assets;

use davidxu\base\assets\BaseAppAsset;
use yii\web\AssetBundle;
use Yii;

class SummernoteAsset extends AssetBundle
{
    const BOOTSTRAP_VERSION_4 = '4.x';
    const BOOTSTRAP_VERSION_5 = '5.x';

    public $sourcePath = '@npm/summernote/dist/';
    public $css = [
    ];
    public $js = [
    ];
    public function init()
    {
        $min = YII_ENV_DEV ? '' : '.min';
        $this->getBsVersion($min);
        parent::init();
    }
    
    /**
     * Sets language for the widget
     * @param string $lang the language code
     * @return $this
     */
    public function setLanguage($lang)
    {
        if (empty($lang) || substr($lang, 0, 2) == 'en') {
            return $this;
        }
        $this->js[] = 'lang/summernote-' . $lang . '.js';
    }

    /**
     * @param string $min Use 'min' code for production environment
     * @return void
     */
    private function getBsVersion($min)
    {
        $bsVersion = Yii::$app->params['bsVersion'];
        if (isset($bsVersion)) {
            if ($bsVersion === self::BOOTSTRAP_VERSION_4) {
                $ver = 'bs4';
            } elseif ($bsVersion === self::BOOTSTRAP_VERSION_5) {
                $ver = 'bs5';
            } else {
                $ver = 'lite';
            }
            $this->css[] = 'summernote-' . $ver . $min . '.css';
            $this->js[] = 'summernote-' . $ver . $min . '.js';
        } else {
            $this->css[] = 'summernote' . $min . '.css';
            $this->js[] = 'summernote' . $min . '.js';
        }
    }

    /**
     * @var array
     */
    public $depends = [
        BaseAppAsset::class,
    ];
}
