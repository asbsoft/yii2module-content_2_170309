<?php

namespace asb\yii2\modules\content_2_170309\models;

use asb\yii2\modules\content_2_170309\web\ContentUrlRule;

use asb\yii2\common_2_170212\i18n\LangHelper;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class ContentRoutesBootstrap extends Content
{
    public $moduleUid;

    public function getRoutes($root = 0, $prefix = '', $lang = null)
    {
        if (empty($lang)) {
            $lang = LangHelper::normalizeLangCode(Yii::$app->language);
        }

        $routes = $this->getChildRoutes($root, $prefix, $lang);
        return $routes;
    }

    protected function getChildRoutes($parentId, $prefix, $lang)
    {
        $routes = [];

        $children = Content::nodeChildren($parentId, $lang);

        foreach ($children as $child) {
            if (!$child->is_visible || empty($child->i18n[$lang]->title)) continue;

            $childPrefix = ($prefix ? "{$prefix}/" : '') . $child->slug;
            $routes = ArrayHelper::merge($routes, $this->getChildRoutes($child->id, $childPrefix, $lang));
        }

        $node = Content::node($parentId);
        //if ($parentId > 0 && !empty($node->text)) { // may be text for another language
        if ($parentId > 0 && !empty($node->i18n[$lang]->text)) {
            $rule = [
                'class'     => ContentUrlRule::className(),
                'pattern'   => $prefix,
                'route'     => "{$this->moduleUid}/main/view", // action
                'contentId' => $parentId,
            ];
            $routes[] = $rule;
        }
        return $routes;
    }

}
