<?php

namespace davidxu\summernote\assets;

use yii\web\AssetBundle;
use yii\bootstrap4\BootstrapAsset;
use yii\web\YiiAsset;
use Yii;

class CodeMirrorAsset extends AssetBundle
{
    public $sourcePath = '@npm/codemirror/';
    public $css = [
        'lib/codemirror.css',
    ];
    public $js = [
        'lib/codemirror.js',
        'mode/xml/xml.js',
    ];

    public function setCodeMirrorTheme($theme)
    {
        if (empty($theme) || !$theme) {
            return $this->setAssetFile('css', "theme/monokai");
        } else {
            return $this->setAssetFile('css', "theme/$theme");
        }
    }

    /**
     * Sets a JS or CSS asset file
     * @return $this
     */
    protected function setAssetFile($ext, $file)
    {
        $this->{$ext}[] = "{$file}.{$ext}";
        return $this;
    }

    /**
     * @var array
     */
    public $depends = [
        YiiAsset::class,
        BootstrapAsset::class,
    ];
}