<?php

namespace davidxu\summernote;

use davidxu\base\enums\QiniuUploadRegionEnum;
use davidxu\base\enums\UploadTypeEnum;
use davidxu\base\helpers\StringHelper;
use davidxu\config\helpers\ArrayHelper;
use davidxu\summernote\assets\CodeMirrorAsset;
use davidxu\summernote\assets\QiniuJsAsset;
use davidxu\summernote\assets\SummernoteAsset;
use davidxu\summernote\assets\SummernotePluginsAsset;
use Qiniu\Auth;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;
use davidxu\base\widgets\InputWidget;
use Yii;

/**
 * Summernote Class
 * @property array $codeMirrorOptions
 */
class Summernote extends InputWidget
{
    /** @var array */
    public $clientOptions = [
        'toolbar' => [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['fontname', ['fontname']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'file', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']],
        ],
        'popover' => [
            'image' => [
                ['remove', ['removeFile']],
            ]
        ],
    ];
    public $codeMirrorOptions = [];
    /** @var array */
    private $_encodedClientOptions;
    private $_encodedMetaData;

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!isset($this->clientOptions['lang']) && Yii::$app->language !== 'en-US') {
            $this->clientOptions['lang'] = Yii::$app->language;
//            $this->clientOptions['lang'] = substr(Yii::$app->language, 0, 2);
        }
        if (!isset($this->clientOptions['height'])) {
            $this->clientOptions['height'] = 150;
        }
        if (!isset($this->codeMirrorOptions['lineNumbers'])) {
            $this->codeMirrorOptions['lineNumbers'] = true;
        }
        if (!isset($this->codeMirrorOptions['theme'])) {
            $this->codeMirrorOptions['theme'] = 'monokai';
        }
        $this->clientOptions['codemirror'] = $this->codeMirrorOptions;

        $this->options['class'] = 'form-control';
        if (!$this->hasModel()) {
            $this->options['id'] = $this->getFieldId();
        }
        parent::init();
        $_view = $this->getView();
        $this->registerAssets($_view, $this->clientOptions['lang'], $this->codeMirrorOptions['theme']);

        if ($this->drive === UploadTypeEnum::DRIVE_QINIU) {
            $this->metaData = ArrayHelper::merge([
                'x:store_in_db' => (string)$this->storeInDB,
                'x:member_id' => Yii::$app->user->isGuest ? '0' : (string)(Yii::$app->user->id),
                'x:upload_ip' => (string)(Yii::$app->request->remoteIP),
            ], $this->metaData);
        }
        if ($this->drive === UploadTypeEnum::DRIVE_LOCAL) {
            $this->metaData['file_field'] = $this->name;
            $this->metaData['store_in_db'] = $this->storeInDB;
            if (Yii::$app->request->enableCsrfValidation) {
                $this->metaData[Yii::$app->request->csrfParam] = Yii::$app->request->getCsrfToken();
            }
        }

        $this->_encodedMetaData = Json::encode($this->metaData);
        $this->configureClientOptions();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->registerScripts();
        echo $this->hasModel()
            ? Html::activeTextarea($this->model, $this->attribute, $this->options)
            : Html::textarea($this->name, $this->value, $this->options);
    }

    private function registerAssets($view, $lang, $theme)
    {
        CodeMirrorAsset::register($view)
            ->setTheme($this->clientOptions['codemirror']['theme']);
        SummernoteAsset::register($view)->setLanguage($this->clientOptions['lang']);
        SummernotePluginsAsset::register($view);
        if ((bool)$this->isQiniuDrive()) {
            QiniuJsAsset::register($view);
        }
    }

    private function getFieldId()
    {
        return $this->hasModel() ? Html::getInputId($this->model, $this->attribute) : StringHelper::getInputId($this->name);
    }

    private function registerScripts()
    {
        $js = <<<JS
function progressBody(percent, progress_class) {
    if (progress_class === '' || progress_class === null || typeof progress_class === 'undefined') {
        progress_class = 'progress-bar-animated progress-bar-striped bg-info'
    }
    return [
        '<div class="progress">',
        '<div class="progress-bar ',
        progress_class,
        ' " ',
        'role="progressbar" aria-valuenow="',
        percent,
        '" aria-valuemin="0" aria-valuemax="100" style="width: ' ,
        percent,
        '%"> ',
        percent,
        '% </div>',
        '</div>',
    ].join('')
}

function uploadFilesToQiniu(editor, file) {
    const fileInfo = getFileInfo(file)
    let customVars = {$this->_encodedMetaData}
    customVars['x:file_type'] = fileInfo.fileType
    const putExtra = {
        fname: fileInfo.name,
        mimeType: fileInfo.mimeType,
        customVars: customVars,
    }
    const config = {
        useCdnDomain: true,
        debugLogLevel: true,
    }

    const observable = qiniu.upload(file, fileInfo.key, '{$this->getQiniuToken()}', putExtra, config)
    const observer = {
        next(res) {
            sweetAlertToast.update({
                html: progressBody(res.total.percent.toFixed(2))
            })
        }, 
        error(err) {
            sweetAlertToast.update({
                toast: true,
                position: 'top-end',
                html: '',
                title: err.data.error,
                icon: 'error'
            })
        }, 
        complete(res) {
            sweetAlertToast.close()
            if (res.success) {
                insertFile(res.result)
            }
        }
    }
    const subscription = observable.subscribe(observer)
    sweetAlertToast.fire({
        allowEscapeKey: false,
    })
}

function uploadFilesToLocal(editor, file) {
    sweetAlertToast.fire({
        allowEscapeKey: false,
    })
    
    const blobSlice = File.prototype.slice || File.prototype.mozSlice || File.prototype.webkitSlice
    const fileInfo = getFileInfo(file)
    const chunkFileKey = (fileInfo.key).replace(/\//g, '_').replace(/\./g, '_')
    let chunkSize = parseInt('{$this->chunkSize}')
    const totalChunks = Math.ceil(file.size / chunkSize)
    let currentChunkIndex = 0
    let formData = new FormData()
    $.each({$this->_encodedMetaData}, function (key, value) {
        formData.append(key,value)
    })
    formData.append('size', fileInfo.size)
    formData.append('extension', fileInfo.extension)
    formData.append('chunk_key', chunkFileKey)
    formData.append('total_chunks', totalChunks)
    //upload
    const _sendFile = (currentChunkIndex) => {
        const start = currentChunkIndex * chunkSize;
        const end = Math.min(file.size, start + chunkSize);
        formData.append('chunk_index', currentChunkIndex)
        if (currentChunkIndex < totalChunks) {
            formData.append('file', blobSlice.call(file, start, end))
        }
        $.ajax({
            url: '{$this->url}',
            data: formData,
            type: 'POST',
            dataType: 'json',
            contentType:false,
            processData:false,
            xhr:function() {
                let myXhr = $.ajaxSettings.xhr()
                if (myXhr.upload) {
                    myXhr.upload.addEventListener('progress',function(e) {
                        // let percent = (100 * e.loaded / e.total).toFixed(2)
                        let percent = (100 * e.loaded / file.size).toFixed(0)
                        sweetAlertToast.update({
                            html: progressBody(percent)
                        })
                    }) // for handling the progress of the upload
                }
                return myXhr
            },     
            success: function (response) {
                currentChunkIndex++
                if (response.success || response.success === 'true') {
                    if (currentChunkIndex < totalChunks) {
                        _sendFile(currentChunkIndex)
                    } else {
                        sweetAlertToast.update({
                            html: progressBody('100', 'bg-success')
                        })
                        // All chuncks sent successfully
                        if (formData.has('file')) {
                            formData.delete('file')
                        }
                        //Add all file information
                        $.each(fileInfo, function (key, value) {
                            formData.set(key, value)
                        })
                        formData.append('eof', true)
                        $.ajax({
                            url: '{$this->url}',
                            data: formData,
                            type: 'POST',
                            dataType: 'json',
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                if (response.success || response.success === 'true') {
                                    sweetAlertToast.close()
                                    insertFile(response.result)
                                }
                            }
                        })
                    }
                } else {
                    sweetAlertToast.update({
                        toast: true,
                        position: 'top-end',
                        html: '',
                        title: response.result,
                        icon: 'error',
                    })
                }
                // endof all chunks uploads
            }
        })
    }
    _sendFile(currentChunkIndex)
}

function insertFile(info) {
    if (info.file_type === 'images') {
        node = $('<img>').attr('src', info.path).attr('class', 'img-fluid').attr('alt', info.name)[0]
        editorEl.summernote('insertNode', node)
        return;
    } else if (info.file_type === 'videos') {
        node = $('<video controls>').attr('src', info.path).attr('class', 'mw-100')[0]
        editorEl.summernote('insertNode', node)
        return;
    } if (info.file_type === 'audios') {
        node = $('<audio controls>').attr('src', info.path).attr('class', 'mw-100')[0]
        editorEl.summernote('insertNode', node)
        return;
    } else {
        editorEl.summernote('createLink', {
            text: info.name,
            url: info.path,
            isNewWindow: true
        })
        return;
    }
}

/**
* get file info
* @param file
* @returns {{extension: string, size, mime_type, file_type: string, name, key: string}}
*/
function getFileInfo(file) {
    const mimeType = (file.type.split('/', 1)[0]).toLowerCase()
    let fileType = 'others'
    if (mimeType === 'image') {
        fileType = 'images'
    } else if (mimeType === 'video') {
        fileType = 'videos'
    } else if (mimeType === 'audio') {
        fileType = 'audios'
    }
    const extension = (file.name.substr(file.name.lastIndexOf('.'))).toLowerCase()
    const key = '{$this->uploadBasePath}' + fileType + '/' + generateKey() + extension
    return {
        name: file.name,
        extension: extension,
        key: key,
        size: file.size,
        mime_type: file.type,
        file_type: fileType
    }
}

function uploadFiles(editor, file) {
    if ({$this->isQiniuDrive()}) {
        uploadFilesToQiniu(editor, file)
    } else if ({$this->isLocalDrive()}) {
        uploadFilesToLocal(editor, file)
    } else {
        sweetAlertToast.update({
            toast: true,
            position: 'top-end',
            html: '',
            title: 'Something with wrong',
            icon: 'error'
        })
    }
}

sweetAlertToast = Swal.mixin({
    showConfirmButton: false,
    backdrop: `rgba(0, 0, 0, 0.8)`,
    title: '<i class="fas fa-spinner fa-pulse"></i>',
})

function generateKey(length) {
    length = length || 32;
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_';
    let maxPos = chars.length;
    let str = ''
    for (i = 0; i < length; i++) {
        str += chars.charAt(Math.floor(Math.random() * maxPos));
    }
    return str
}

// let reader = new FileReader()
editorEl = $("#{$this->options['id']}")
editorEl.summernote({$this->_encodedClientOptions})
editorEl.on('summernote.image.upload', function (editor, files) {
    uploadFiles(editor, files[0])
})
editorEl.on('summernote.file.upload', function (editor, files) {
    uploadFiles(editor, files[0])
})

JS;
        $_view = $this->getView();
        $_view->registerJs($js);
    }

    private function configureClientOptions()
    {
        $this->_encodedClientOptions = Json::encode(ArrayHelper::merge($this->clientOptions, [
            'callbacks' => [
                'onImageUpload' => new JsExpression('() => {return}'),
                'onFileUpload' => new JsExpression('() => {return}'),
            ],
        ]));
    }
}
