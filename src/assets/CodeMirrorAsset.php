<?php

namespace davidxu\summernote\assets;

use davidxu\base\assets\BaseAppAsset;
use yii\web\AssetBundle;

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

    public function setTheme($theme)
    {
        if (empty($theme) || !$theme) {
            $this->css[] = 'theme/monikai.css';
        } else {
            $this->css[] = 'theme/' . $theme . '.css';
        }
    }

    /**
     * @var array
     */
    public $depends = [
        BaseAppAsset::class,
    ];
}