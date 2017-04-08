<?php

namespace asb\yii2\modules\content_2_170309\models;

use asb\yii2\modules\content_2_170309\Module;

use Yii;
use yii\helpers\Url;

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class ContentMenuBuilder
{
    const MODEL_ALIAS      = 'Content';
    const MODEL_I18N_ALIAS = 'ContentI18n';
    
    /**
     * @return array of all menu items in yii\bootstrap\Nav widget format
     */
    public static function rootMenuItems()
    {
        $menuItems = static::menuItems(0);//echo __METHOD__;var_dump($menuItems);exit;
//echo __METHOD__;var_dump($menuItems[1]['items'][0]);
        return $menuItems;
    }

    /**
     * @return array of menu items in yii\bootstrap\Nav widget format
     */
    public static function menuItems($parentId = 0)
    {
        static::_prepare();
        $lang = static::language();

        $menuItems = [];
        if (empty(static::$_model)) {
            $msg = "Can't find model/module for build menu in " . __METHOD__;
            Yii::error($msg);
            return $menuItems;
        }

        $node = static::$_model->node($parentId);
        if (!empty($node->i18n[$lang]->title) && $node->is_visible) {
            $parentMenuItem = [
                'label' => $node->i18n[$lang]->title,
                //'url' => Url::toRoute([static::$_routeAction, 'id' => $node->id]),
                'url' => static::createLink($node->id),
            ];//echo"URL#{$node->id}: {$parentMenuItem['url']}<br>";
        }

        $children = static::$_model->nodeChildren($parentId);
        $submenuItems = [];
        foreach ($children as $child) {//var_dump($child->attributes);
            if (!empty($child->i18n[$lang]->title) && $child->is_visible) {
                $tmpItems = static::menuItems($child->id);
                if (!empty($tmpItems)) $submenuItems[] = $tmpItems;
            }
        }
        
        if (empty($parentMenuItem)) {
            $menuItems = $submenuItems;
        } else if (empty($submenuItems)) {
            if (!empty($node->i18n[$lang]->text)) $menuItems = $parentMenuItem;
        } else {
            if (!empty($node->i18n[$lang]->text)) array_unshift($submenuItems, $parentMenuItem);
            $menuItems = [
                'label' => $parentMenuItem['label'],
                'items' => $submenuItems,
                'dropDownOptions' => ['class' => 'dropdown-menu'], //!! v.2.0.10
            ];
        }//echo __METHOD__."($parentId)";var_dump($menuItems);

        return $menuItems;
    }

    public static function createLink($id)
    {
        $url = Url::toRoute([static::$_routeAction, 'id' => $id]);//var_dump($url);
        $parts = parse_url($url);//var_dump($parts);
        if (!empty($parts['path']))
        {
            $url = $parts['path'];
            if (!empty($parts['query'])) {
                $params = explode('&', $parts['query']);//var_dump($params);
                $query = '';
                foreach ($params as $param) {
                    $result = preg_replace('|(id=\d+)|', '', $param);
                    if (empty($result)) continue;
                    $query .= $result . '&';
                }
                $query = trim($query, '&');//var_dump($query);
                if (!empty($query)) $url .= '?' . $query;
            }
        }//var_dump($url);//exit;
        return $url;
    }

    //protected static $_module;
    protected static $_routeAction;
    protected static $_model;
    //protected static $_langHelper;
    //protected static $_langCodeMain;
    protected static function _prepare()
    {
        if (empty(static::$_module)) {
            $module = Module::getModuleByClassname(Module::className());
            if (!empty($module)) {
                static::$_routeAction = "/{$module->uniqueId}/main/view";
                //static::$_module = $module;
                static::$_model = $module::model(self::MODEL_ALIAS);
                //static::$_langHelper = new $module->langHelper;
                //static::$_langCodeMain = static::$_langHelper->normalizeLangCode(Yii::$app->language);
            }
        }
    }

    protected static $_language;
    /**
     * Get normalized system language.
     */
    public static function language()
    {
        if (empty(static::$_language)) {
            $module = Module::getModuleByClassname(Module::className());
            $langHelper = $module->langHelper;
            static::$_language = $langHelper::normalizeLangCode(Yii::$app->language);
        }
        return static::$_language;
    }

}
