<?php

namespace asb\yii2\modules\content_2_170309\models;

use asb\yii2\modules\content_2_170309\Module;
use asb\yii2\common_2_170212\web\RoutesBuilder;
use asb\yii2\common_2_170212\i18n\LangHelper;

use Yii;
use yii\helpers\Url;
use yii\base\InvalidParamException;

use Exception;

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class ContentMenuBuilder
{
    const MODEL_ALIAS      = 'Content';
    const MODEL_I18N_ALIAS = 'ContentI18n';

    protected static $_sysControllerUid;
    protected static $_routeActionView;
    protected static $_routeActionShow;
    protected static $_model;
    /** Init usable vars */
    protected static function _prepare()
    {
        if (empty(static::$_module)) {
            $module = Module::getModuleByClassname(Module::className());
            if (!empty($module)) {
                static::$_sysControllerUid = "/sys/main";
                static::$_routeActionView = "/{$module->uniqueId}/main/view";
                static::$_routeActionShow = "/{$module->uniqueId}/main/show";
                static::$_model = $module::model(self::MODEL_ALIAS);
            }
        }
    }
    
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
              //throw new InvalidParamException($msg);
                return [];
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
            $url = static::createContentLink($node); // may be false if node has no text but only menu item title
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
            if (!empty($node->i18n[$lang]->text) || static::checkRoutesLink($node) || !empty($node->route)) {
                $menuItems = $parentMenuItem;
            }
        } else { // duplicate parent link in submenu
            if (!empty($node->i18n[$lang]->text) || static::checkRoutesLink($node) || !empty($node->route)) {
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
     * @param Content $node
     * @return string|false
     */
    protected static function createContentLink($node)
    {
        static::_prepare();
        $url = false;

        // node with external/internal link: get URL from 'route' field
        if (empty($node->text) && !empty($node->route)) {
            $url = static::routeToLink($node->route);
        }

        // site tree content node: create URL for node from visible site tree if this node has original route
        if ($url === false) {
            $url = Url::toRoute([static::$_routeActionView, 'id' => $node->id]);
            $parts = parse_url($url);
            if (!empty($parts['path'])) {
                $url = $parts['path'];
                if (strstr($url, static::$_routeActionView)) { // it's fake link contain action UID and GET-parameter "id={$id}"
                    $url = false;
                }
            }
        }

        // node has content but any links yet: create URL for node out of visible site tree (submenu tree)
        if ($url === false && empty($node->route) && !empty($node->text)) {
            $url = Url::toRoute([static::$_routeActionShow, 'id' => $node->id, 'slug' => $node->slug]);
        }

        return $url;
    }

    /**
     * Convert route to link.
     * @param string $strRoute string representation of route in PHP5.4+ []-notation
     * @param string $ctrlLinkPrefix current controller links prefix
     * @return string|false
     */
    public static function routeToLink($strRoute, $ctrlLinkPrefix = null)
    {
        if (empty($ctrlLinkPrefix)) {
            static::_prepare();
            $ctrlLinkPrefix = Url::toRoute([static::$_sysControllerUid]);
        }
        $strRoute = trim($strRoute);
        $url = $route = false;
        if (substr($strRoute, 0, 1) == '=') {  // external link begin with '='
            $url = trim(substr($strRoute, 1));
            //$url = urlencode($url);
        } else {
            if (substr($strRoute, 0, 1) == '[') {  // array-string
                $route = static::convertRouteStrToArray($strRoute);  // convert string array definition to array
            } elseif (substr($strRoute, 0, 1) == '/') {  // internal link
                $route = $strRoute;
            }
            if (!$route) {
                $url = false;
            } else {
                try {
                    $url = Url::toRoute($route);
                    $parts = parse_url($url);
                    if (0 === strpos($parts['path'], $ctrlLinkPrefix)) {  // illegal link
                        static::$errorRouteConvert = "Illegal link resolved";
                        $url = false;
                    }
                } catch (InvalidParamException $ex) {
                    static::$errorRouteConvert = $ex->getMessage();
                    $url = false;
                }
            }
        }
        return $url;
    }
    public static $errorRouteConvert = '';
    /**
     * Convert string array definition to array.
     * @param string $strRoute string representation of route
     * @return array|false array representation of route or false on error
     */
    protected static function convertRouteStrToArray($strRoute)
    {
/*
        try {
            $route = @eval("return $strRoute;"); // security problem
        } catch (Exception $ex) {
            return false;
        }
*/
        $str = trim($strRoute);
        if (substr($str, 0, 1) == '[' && substr($str, -1, 1) == ']') {
            $str = trim($str, "[]");
        } else {
            static::$errorRouteConvert = "Array syntax: not found '[' or ']'";
            return false;
        }
        $array = [];
        $i = 0;
        $parts = explode(',', $str);
        foreach ($parts as $next) {
            $element = explode("=>", $next);
            if (is_array($element) && count($element) == 1) {
                $val = trim($element[0]);
                if ((substr($val, 0, 1) == '"' && substr($val, -1, 1) == '"')
                 || (substr($val, 0, 1) == "'" && substr($val, -1, 1) == "'")
                ) {
                    $array[$i++] = trim($val, "\"'");
                } else {
                    static::$errorRouteConvert = "Array element syntax: '$val'";
                    return false;
                }
            } elseif (is_array($element) && count($element) == 2) {
                $key = trim($element[0]);
                $val = trim($element[1]);
                if (
                    ((substr($key, 0, 1) == '"' && substr($key, -1, 1) == '"') ||
                     (substr($key, 0, 1) == "'" && substr($key, -1, 1) == "'"))
                 &&
                    ((substr($val, 0, 1) == '"' && substr($val, -1, 1) == '"') ||
                     (substr($val, 0, 1) == "'" && substr($val, -1, 1) == "'") ||
                     is_numeric($val))
                ) {
                    $array[trim($key, "\"'")] = trim($val, "\"'");
                } else {
                    static::$errorRouteConvert = "Array element syntax: '$key => $val'";
                    return false;
                }
            } else {
                static::$errorRouteConvert = "Array element syntax";
                return false;
            }
        }
        return $array;
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
