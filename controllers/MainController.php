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
     * @return mixed
     * @throws NotFoundHttpException if content not found or unvisible
     */
    public function actionView($id = 0, $strict = false)
    {//echo __METHOD__."($id)";

        $model = $this->findContent($id, !$strict);//var_dump($model);exit;

        if (empty($model->i18n[$this->lang]->text)) {
            throw new NotFoundHttpException(Yii::t($this->tcModule, 'Content not found'));
        }//var_dump($i18n->attributes);

        $model->correctSelectedText();
        $params = $this->getDefaultParams($model);
        $text = TextProcessor::textPreprocess($model->i18n[$this->lang]->text, $params);//var_dump($text);

        return $this->render('view', [
            'text' => $text,
        ]);
    }
    /**
     * Find content for $fromId.
     * If not found and $tryNext search content at first by order visible child(s).
     * @param integer $fromId
     * @param boolean $tryNext
     * @return Content|empty
     * @throws NotFoundHttpException if content not found or unvisible
     */
    public function findContent($fromId, $tryNext = false)
    {//echo __METHOD__."($fromId,$tryNext)<br>";
        $model = $this->model->node($fromId);//if(!empty($model))var_dump($model->attributes);else var_dump($model);

        if (!empty($model->i18n[$this->lang]->title) && !empty($model->i18n[$this->lang]->text)) {//echo __METHOD__."($fromId,$tryNext):FOUND:#{$model->id}<br>";exit;
            return $model;
      //} else if ((empty($model) || !$model->is_visible) && !$tryNext) {
        } else if (!$tryNext || (isset($model->is_visible) && !$model->is_visible)) {//echo __METHOD__."($fromId,$tryNext):NOTfound<br>";exit;
            throw new NotFoundHttpException(Yii::t($this->tcModule, 'Content not found'));
        } else { // search content between children
            $children = $this->model->nodeChildren($fromId);//var_dump(count($children));
            foreach ($children as $child) {
                if ($child->is_visible && !empty($child->i18n[$this->lang]->title)) {
                    if (!empty($child->i18n[$this->lang]->text)) {//echo __METHOD__."($fromId,$tryNext):found CHILD:#{$child->id}<br>";exit;
                        return $child;
                    } else {
                        return $this->findContent($child->id, $tryNext);
                    }
                    break;
                }
            }
        }//echo __METHOD__."($fromId,$tryNext):return:FALSE<br>";
        return false;
    }

    /**
     * @inheritdoc
     */
    public function findLayoutFile($view)
    {//echo __METHOD__;var_dump($this->_contentLayout);
        $layout = parent::findLayoutFile($view);//var_dump($layout);
        if (!empty($this->_contentLayout)) {
            $fname = Yii::getAlias($this->_contentLayout);
            if (is_file($fname)) $layout = $fname;
            else if (is_file($fname . '.php'))  $layout = $fname . '.php';
            else if (is_file($fname . '.twig')) $layout = $fname . '.twig';
        }//var_dump($layout);exit;
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
    {//echo __METHOD__."($id, $lang)";var_dump($params);
        if (empty($lang)) $lang = Yii::$app->language;
        $langHelper = $this->module->langHelper;
        $lang = $langHelper::normalizeLangCode($lang);

        if (is_numeric($id)) {
            $contentId = $id;
        } else {
            $contentId = $this->model->getIdBySlugPath($id);
        }//var_dump($contentId);

        if (empty($contentId)) {
            $msg = __METHOD__ . ": illegal id = '$id'.";
            Yii::error($msg);
            return '';
        }
        $node = $this->model->node($contentId);
        if (empty($node)) {
            $msg = __METHOD__ . ": node id = '{$contentId}' not found.";
            Yii::error($msg);
            return '';
        }

        $text = $node->i18n[$lang]->text;

        // processing parameters in format '{{param}}', translation table get from unserialized $params
        $defParams = $this->getDefaultParams($node);
        $params = @unserialize($params);//var_dump($params);
        $params = ArrayHelper::merge($defParams, $params);//var_dump($params);
        $text = TextProcessor::textPreprocess($text, $params);
        return $text;
    }

    /**
     * Get default substitution params.
     * @param Content $model
     * @return array
     */
    public function getDefaultParams($model)
    {
        $fmt = new Formatter;
        $params = [
            'title'   => $model->title,
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
