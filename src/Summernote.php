<?php

namespace davidxu\summernote;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use davidxu\summernote\assets\SummernoteAsset;

class Summernote extends InputWidget
{
    
    /** @var ?string */
    public $fileField = null;
    /** @var ?string */
    public $fileModelClass = null;
    /** @var array */
    public $options = [];
    /** @var array */
    public $settings = [];
    /** @var array */
    public $clientOptions = [];
    /** @var array */
    private $defaultOptions = ['class' => 'form-control'];

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        if (!isset($this->settings['lang']) && Yii::$app->language !== 'en-US') {
            $this->settings['lang'] = substr(Yii::$app->language, 0, 2);
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
        // if ($this->fileField && $this->fileModelClass) {
        //     $classname = new ClassnameEncoder($this->fileModelClass);
        //     $view->registerJs("summernoteParams.fileField = '{$this->fileField}'");
        //     $view->registerJs("summernoteParams.fileClass = '{$classname}'");
        // } else {
        //     $view->registerJs("summernoteParams.callbacks = {}");
        // }
        $view->registerJs('jQuery( "#' . $this->options['id'] . '" ).summernote(Json::encode($this->settings));');
    }

    private function registerAssets()
    {
        $view = $this->getView();
        SummernoteAsset::register($view)->setLanguage($this->settings['lang']);
    }
}