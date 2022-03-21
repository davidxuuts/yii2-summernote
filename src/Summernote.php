<?php

namespace davidxu\summernote;

use davidxu\summernote\assets\CodeMirrorAsset;
use davidxu\summernote\assets\SummernoteAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;

/**
 * Summernote Class
 *
 * @property array $options
 * @property array $clientOptions
 */
class Summernote extends InputWidget
{
    /** @var array */
    public $clientOptions = [];
    /** @var array */
    private $defaultOptions = ['class' => 'form-control'];

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        var_dump($this->clientOptions);
        if (!isset($this->clientOptions['lang']) && Yii::$app->language !== 'en-US') {
            $this->clientOptions['lang'] = substr(Yii::$app->language, 0, 2);
        }

        if (!isset($this->clientOptions['codemirror']['theme'])) {
            $this->clientOptions['codemirror']['theme'] = 'monokai';
        }
        $this->options = array_merge($this->defaultOptions, $this->options);
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $view = $this->getView();
        $this->registerAssets();
        echo $this->hasModel()
            ? Html::activeTextarea($this->model, $this->attribute, $this->options)
            : Html::textarea($this->name, $this->value, $this->options);
        $view->registerJs('jQuery( "#' . $this->options['id'] . '" ).summernote(' . Json::encode($this->clientOptions) . ');');
    }

    private function registerAssets()
    {
        $view = $this->getView();
        CodeMirrorAsset::register($view)->setCodeMirrorTheme($this->clientOptions['codemirror']['theme']);
        SummernoteAsset::register($view)->setLanguage($this->clientOptions['lang']);
    }
}
