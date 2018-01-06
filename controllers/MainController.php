<?php

namespace asb\yii2\modules\content_2_170309\controllers;

use asb\yii2\modules\content_2_170309\models\Content;
use asb\yii2\modules\content_2_170309\models\ContentI18n;
use asb\yii2\modules\content_2_170309\models\Formatter;
use asb\yii2\modules\content_2_170309\models\TextProcessor;

use asb\yii2\common_2_170212\controllers\BaseMultilangController;

use Yii;
use yii\web\NotFoundHttpException;
use yii\helpers\ArrayHelper;

/**
 * Main frontend controller.
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class MainController extends BaseMultilangController
{
    public $model;

    public $lang;
    
    protected $_contentLayout;
    
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        
        $lh = $this->module->langHelper;
        $this->lang = $lh::normalizeLangCode(Yii::$app->language);

        $this->model = $this->module->model('Content');
    }

    /**
     * Render content as a web page,
     * Text will get some substitutions, at first '{{title}}' will change to content title (menu item).
     * @param integer $id content ID, default 0 - root
     * @param boolean $strict search regime:
     *     if content not found and $strict = true throws exception
     *     if content not found and $strict = false find first child with content
     * @param string $langCode language code
     * @param boolean $layout show page with layout or render partial if false
     * @param boolean $showEmptyContent show without texts instead of exception
     * @return mixed
     * @throws NotFoundHttpException if content not found or unvisible
     */
    public function actionView($id = 0, $strict = false, $langCode = null, $layout = true, $showEmptyContent = false)
    {
        $module = $this->module;
        if ($id == 0 && $module->params['useExternalStartPage'] && !empty($module::$savedDefaultRoute)) {
            $action = $module::$savedDefaultRoute;
            return Yii::$app->runAction($action);
        }
        
        if (empty($langCode)) $langCode = $this->lang;
        $model = $this->findContent($id, !$strict, $showEmptyContent);

        if (empty($model) || ($strict && empty($model->i18n[$langCode]->text))) {
            throw new NotFoundHttpException(Yii::t($this->tcModule, 'Content not found'));
        }

        $model->correctSelectedText();
        $params = $this->getDefaultParams($model, $langCode);
        $text = TextProcessor::textPreprocess($model->i18n[$langCode]->text, $params);

        $data = [
            'title' => $model->i18n[$langCode]->title,
            'text' => $text,
        ];
        if ($layout) {
            return $this->render('view', $data);
        } else {
            return $this->renderPartial('view', $data);
        }
    }
    /**
     * Find content for $fromId.
     * If not found and $tryNext search content at first by order visible child(s).
     * @param integer $fromId
     * @param boolean $tryNext
     * @param boolean $showEmptyContent return model without texts instead of exception
     * @return Content|empty
     * @throws NotFoundHttpException if content not found or unvisible
     */
    public function findContent($fromId, $tryNext = false, $showEmptyContent = false)
    {
        $model = $this->model->node($fromId);

        if (!empty($model->i18n[$this->lang]->title) && !empty($model->i18n[$this->lang]->text)) {
            return $model;
        } else if ($showEmptyContent && !empty($model->i18n[$this->lang])) {
            return $model; // in backend can view content without title/body
      //} else if ((empty($model) || !$model->is_visible) && !$tryNext) {
        } else if (!$tryNext || (isset($model->is_visible) && !$model->is_visible)) {
            throw new NotFoundHttpException(Yii::t($this->tcModule, 'Content not found'));
        } else { // search content between children
            $children = $this->model->nodeChildren($fromId);
            foreach ($children as $child) {
                if ($child->is_visible && !empty($child->i18n[$this->lang]->title)) {
                    if (!empty($child->i18n[$this->lang]->text)) {
                        return $child;
                    } else {
                        return $this->findContent($child->id, $tryNext);
                    }
                    break;
                }
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function findLayoutFile($view)
    {
        $layout = parent::findLayoutFile($view);
        if (!empty($this->_contentLayout)) {
            $fname = Yii::getAlias($this->_contentLayout);
            if (is_file($fname)) $layout = $fname;
            else if (is_file($fname . '.php'))  $layout = $fname . '.php';
            else if (is_file($fname . '.twig')) $layout = $fname . '.twig';
        }
        return $layout;
    }

    /**
     * Render text block by id,
     * For runAction() at template only.
     * @param string|integer $id
     * @param string $lang
     * @param string $params additional params - only string - need serialize for array
     * @return string  result text or '' on any error
     */
    public function actionRender($id, $lang = null, $params = '')
    {
        if (empty($lang)) $lang = Yii::$app->language;
        $langHelper = $this->module->langHelper;
        $lang = $langHelper::normalizeLangCode($lang);

        if (is_numeric($id)) {
            $contentId = $id;
        } else {
            $contentId = $this->model->getIdBySlugPath($id);
        }

        if (empty($contentId)) {
            $msg = __METHOD__ . ": illegal id = '$id'.";
            Yii::error($msg);
            return '';
        }
        $node = $this->model->node($contentId);
        if (empty($node)) {
            $msg = __METHOD__ . ": node id='{$contentId}' not found.";
            Yii::error($msg);
            return '';
        }

        if (isset($node->i18n[$lang]->text)) {
            $text = $node->i18n[$lang]->text;
        } else {
            $msg = __METHOD__ . ": for node id='{$contentId}', lang='$lang' not found text.";
            Yii::error($msg);
            return '';
        }

        // processing parameters in format '{{param}}', translation table get from unserialized $params
        $defParams = $this->getDefaultParams($node, $lang);
        $params = @unserialize($params);
        if (!is_array($params)) { // $params must be array (on error unserialize() return false)
            $params = [];
        }
        $params = ArrayHelper::merge($defParams, $params);
        $text = TextProcessor::textPreprocess($text, $params);
        return $text;
    }

    /**
     * Get default substitution params.
     * @param Content $model
     * @param string $langCode
     * @return array
     */
    public function getDefaultParams($model, $langCode)
    {
        $fmt = new Formatter;
        $params = [
            'title'   => $model->i18n[$langCode]->title, // $model->title = const (for def lang)
            'slug'    => $model->slug,
            'path'    => $model::getNodePath($model),
            'owner'   => $fmt->asUsername($model->owner_id),
            'created' => $model->create_time,
            'updated' => $model->update_time,
            //...
        ];
        return $params;
    }

}
