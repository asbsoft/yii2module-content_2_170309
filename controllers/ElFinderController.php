<?php
namespace asb\yii2\modules\content_2_170309\controllers;

use asb\yii2\common_2_170212\widgets\ckeditor\ElFinderController as BaseController;

use mihaildev\elfinder\PathController;

use Yii;
use yii\filters\AccessControl;
use yii\helpers\FileHelper;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class ElFinderController extends BaseController
{
    /** Default role(s) for all actions. Use instead of behaviors()['access'] */
    public $access = ['roleContentAuthor', 'roleContentModerator'];

    /**
     * @inheritdoc
     * Need to add news id to connector's URL. Every news will have it's own uploads dir.
     */
    public function getManagerOptions()
    {
        $options = parent::getManagerOptions();
        $id = Yii::$app->request->getQueryParam('id', 0);
        $options['url'] = Url::toRoute(['connect', 'id' => $id]);
        return $options;
    }

    /**
     * @inheritdoc
     */
    public function __construct($id, $module, $config = [])
    {
        $contentId = Yii::$app->request->getQueryParam('id', 0);
        $newsModel = $module::model('Content');
        $subdir = $newsModel::getImageSubdir($contentId);
        
        $this->uploadsNewsUrl = Yii::getAlias($module->params['uploadsContentUrl']) . '/' . $subdir;
        $this->uploadsNewsDir = Yii::getAlias($module->params['uploadsContentDir']) . '/' . $subdir;

        parent::__construct($id, $module, $config);
     }
}
