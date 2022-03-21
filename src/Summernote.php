<?php

namespace davidxu\summernote;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use davidxu\summernote\assets\SummernoteAsset;

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
        if (!isset($this->clientOptions['lang']) && Yii::$app->language !== 'en-US') {
            $this->clientOptions['lang'] = substr(Yii::$app->language, 0, 2);
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
        SummernoteAsset::register($view)->setLanguage($this->clientOptions['lang']);
    }
}
