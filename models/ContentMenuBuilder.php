<?php

namespace asb\yii2\modules\content_2_170309\models;

use asb\yii2\modules\content_2_170309\Module;
use asb\yii2\common_2_170212\web\RoutesBuilder;
use asb\yii2\common_2_170212\i18n\LangHelper;

use Yii;
use yii\helpers\Url;
use yii\base\InvalidParamException;

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
        return static::submenuItems('/');
    }

    /**
     * @param string $nodePath path to root of submenu in content nodes tree, format '/menus/custom-layout/left-menu'
     * @return array of submenu items in yii\bootstrap\Nav widget format
     */
    public static function submenuItems($nodePath = '/')
    {
        static::_prepare();
        $model = static::$_model;

        if (trim($nodePath, '/') === '') {
            $id = 0;  // root of tree
        } else {
            $id = $model::getIdBySlugPath($nodePath);
            if ($id === false) {
                $msg = "Illegal node path '$nodePath' to submenu in content tree";
                Yii::error($msg);
                throw new InvalidParamException($msg);
            }
        }
        $menuItems = static::menuItems($id);

        if (empty($menuItems['items'])) {
            return $menuItems;
        } else {
            return $menuItems['items'];
        }
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
          //throw new InvalidParamException($msg);
            return $menuItems;
        }

        $node = static::$_model->node($parentId);
        if (!empty($node->i18n[$lang]->title) && $node->is_visible) {
            $url = static::createContentLink($node->id); // may be false if node has no text but only menu item title
            if ($url === false) {
                $url = static::checkRoutesLink($node);
            }
            $parentMenuItem = [
                'label' => $node->i18n[$lang]->title,
                'url' => $url,
            ];
        }

        $children = static::$_model->nodeChildren($parentId);
        $submenuItems = [];
        foreach ($children as $child) {
            if (!empty($child->i18n[$lang]->title) && $child->is_visible) {
                $tmpItems = static::menuItems($child->id);
                if (!empty($tmpItems)) {
                    $submenuItems[] = $tmpItems;
                }
            }
        }
        
        if (empty($parentMenuItem)) {
            $menuItems = $submenuItems;
        } else if (empty($submenuItems)) {
            if (!empty($node->i18n[$lang]->text) || static::checkRoutesLink($node)) {
                $menuItems = $parentMenuItem;
            }
        } else { // duplicate parent link in submenu
            if (!empty($node->i18n[$lang]->text) || static::checkRoutesLink($node)) {
                array_unshift($submenuItems, $parentMenuItem);
            }
            $menuItems = [
                'label' => $parentMenuItem['label'],
                'items' => $submenuItems,
                'dropDownOptions' => ['class' => 'dropdown-menu'], // need for v.2.0.10+
            ];
        }

        return $menuItems;
    }

    /**
     * For node without 'text' check if exists such route for any another module.
     * If such route exists will return link for menu.
     * @param Content $node
     * @return string|false
     */
    protected static function checkRoutesLink($node)
    {
        $nodeLink = static::$_model->getNodePath($node);

        // find route 
        $result = false;
        foreach (Yii::$app->urlManager->rules as $nextRule) {
            if (RoutesBuilder::properRule($nextRule, $nodeLink)) {
                $result = true;
                break;
            }
        }
        if ($result) {
            $result = '/' . $nodeLink;
            $lh = static::langHelperClass();
            if ($lh::countActiveLanguages() > 1) {
                $lang = $lh::getLangCode2(static::language());
                $result = '/' . $lang . '/' . $nodeLink;
            }
        }
        return $result;
    }

    /**
     * Create link for node.
     * @param integer $id
     * @return string|false
     */
    protected static function createContentLink($id)
    {
        $url = Url::toRoute([static::$_routeAction, 'id' => $id]);
        $parts = parse_url($url);
        if (!empty($parts['path'])) {
            $url = $parts['path'];
            if (strstr($url, static::$_routeAction)) { // it's fake link contain action UID and GET-parameter "id={$id}"
                $url = false;
            }
        }
        return $url;
    }

    protected static $_routeAction;
    protected static $_model;
    protected static function _prepare()
    {
        if (empty(static::$_module)) {
            $module = Module::getModuleByClassname(Module::className());
            if (!empty($module)) {
                static::$_routeAction = "/{$module->uniqueId}/main/view";
                static::$_model = $module::model(self::MODEL_ALIAS);
            }
        }
    }

    protected static $_langHelper;
    public static function langHelperClass()
    {
        if (empty(static::$_langHelper)) {
            $module = Module::getModuleByClassname(Module::className());
            if (!empty($module)) {
                static::$_langHelper = $module->langHelper;
            } else {
                static::$_langHelper = LangHelper::className();
            }
        }
        return static::$_langHelper;
    }
    protected static $_language;
    /**
     * Get normalized system language.
     */
    public static function language()
    {
        if (empty(static::$_language)) {
            $langHelper = static::langHelperClass();
            static::$_language = $langHelper::normalizeLangCode(Yii::$app->language);
        }
        return static::$_language;
    }

}
