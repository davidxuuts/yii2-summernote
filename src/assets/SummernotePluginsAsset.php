<?php

namespace davidxu\summernote\assets;

use yii\web\AssetBundle;

class SummernotePluginsAsset extends AssetBundle
{
    public $sourcePath = '@davidxu/summernote/plugins/';
    public $css = [
    ];
    public $js = [
        'js/summernote-file.js',
    ];

    /**
     * @var array
     */
    public $depends = [
        SummernoteAsset::class,
    ];
}
