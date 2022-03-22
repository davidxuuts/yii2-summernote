<?php

namespace davidxu\summernote\assets;

use yii\web\AssetBundle;
use yii\bootstrap4\BootstrapAsset;
use yii\web\YiiAsset;
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
//        $bsVersion = Yii::$app->params['bsVersion'];
//        if (isset($bsVersion) && in_array($bsVersion, [self::BOOTSTRAP_VERSION_4, self::BOOTSTRAP_VERSION_5]) !== null) {
//            $this->css[] = 'summernote-' . $bsVersion . $min . '.css';
//            $this->js[] = 'summernote-' . $bsVersion . $min . '.js';
//        } else {
//            $this->css[] = 'summernote' . $min . '.css';
//            $this->js[] = 'summernote' . $min . '.js';
//        }
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
        return $this->setAssetFile('js', "lang/summernote-{$lang}");
    }

    /**
     * Sets a JS or CSS asset file
     * @return $this
     */
    protected function setAssetFile($ext, $file)
    {
        $this->{$ext}[] = YII_DEBUG ? "{$file}.{$ext}" : "{$file}.min.{$ext}";
        return $this;
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
        YiiAsset::class,
        BootstrapAsset::class,
    ];
}