<?php

namespace asb\yii2\modules\content_2_170309\models;

use Yii;
use yii\helpers\Url;
use yii\base\InvalidRouteException;

/**
 * Content text preprocessing.
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class TextProcessor
{
    /** Supportet widgets: array of [ name/alias => class ] */
    public static $widgets = [
        'content' => 'asb\yii2\modules\content_2_170309\widgets\ContentWidget',
        //...
    ];

    /**
     * Text substitutions by $params.
     * @param string $text
     * @param array $params
     * @return string
     */
    public static function textPreprocess($text, $params)
    {
        // translate '{{PARAM}}'
        $trtab = [];
        if (is_array($params)) {
            foreach ((array)$params as $name => $value) {
                $trtab['{{' . $name . '}}'] = $value;
            }
            $text = strtr($text, $trtab);
        }

        // find and process in text '{{%plugin ...params...}}'
        $text = preg_replace_callback('/{{%([^}]+)}}/', 'static::runPlugin', $text);
        
        // erase other '{{...}}'
        $text = preg_replace('/{{[^}]*}}/', '', $text);
        return $text;
    }

    protected static function runPlugin($matches)
    {
         $origText = $matches[0];
         $cmd = $matches[1];
         $cmd = html_entity_decode($cmd, ENT_QUOTES);

         $parts = explode(' ', $cmd, 2);
         $plugin = trim($parts[0]);
         $rest = empty($parts[1]) ? '' : trim($parts[1]);

         $parts = explode(',', $rest);
         $params = [];
         foreach ($parts as $next) {
             $next = trim($next);
             if (empty($next)) continue;

             if (preg_match('/([^=]+)=?(.*)/', $next, $found)) {
                 $name = trim($found[1]);
                 $val = trim($found[2]);
                 $val = trim($val, "'");
                 $val = trim($val, '"');
                 if (empty($val)) {
                     $params[] = $name;
                 } else {
                     $params[$name] = $val;
                 }
             }
         }

         $result = $origText;
         $pluginMethod = "plugin{$plugin}";
         if (method_exists(__CLASS__, $pluginMethod)) {
             $result = static::$pluginMethod($params);
         } else {
            $msg = __METHOD__ . ": text plugin '{$plugin}' not found: {$origText}";
            Yii::error($msg);
         }
         return $result;
    }

    /**
     * Processing "{{%url route='...' {, paramI='...'} }}" as Url::toRoute()
     * or "{{%url url='...', paramN='...',}]" as Url::to()
     * @param array $params
     * @return string result text or '' on any error
     */
    protected static function pluginUrl($params)
    {
        $result = '';
        if (!empty($params['route'])) {
            $route = $params['route'];
            unset($params['route']);
            $result = Url::toRoute($route, $params);
        } elseif (!empty($params['url'])) {
            $url = $params['url'];
            unset($params['url']);
            $result = Url::toRoute($url, $params);
        } else {
            $msg = __METHOD__ . ": text plugin '{{%url}}' must have 'route' or 'url' parameter."
                 . " Real params is " . var_export($params, true);
            Yii::error($msg);
        }
        return $result;
    }

    /**
     * Processing "{{%render widget|action='...', paramN='...',}]"
     * @param array $params
     * @return string result string or '' on any error
     */
    protected static function pluginRender($params)
    {
        $result = '';
        if (!empty($params['action'])) {
            $actionUid = $params['action'];
            unset($params['action']);
            try {
                $result = Yii::$app->runAction($actionUid, $params);
            } catch (InvalidRouteException $ex) {
                $msg = __METHOD__ . ": not found action {{%render action='{$actionUid}'}}."
                     . "\n + Real params is: " . var_export($params, true)
                     . "\n + Exception message is: {$ex->getMessage()}";
                Yii::error($msg);
            }
        } else 
        if (!empty($params['widget'])) {
            $name = $params['widget'];
            unset($params['widget']);
            if (isset(static::$widgets[$name])) {
                $widgetParams = $params;
                $widgetParams['class'] = static::$widgets[$name];
                $widget = Yii::createObject($widgetParams);
                $result = $widget->run();
            } else {
                $msg = __METHOD__ . ": unknown widget {{%render widget='{$name}'}}. Real params is: " . var_export($params, true);
                Yii::error($msg);
            }
        } else {
            $msg = __METHOD__ . ": '{{%render}}' must have 'action' or 'widget' parameter. Real params is: " . var_export($params, true);
            Yii::error($msg);
        }
        return $result;
    }

    /**
     * Processing "{{%htmlsym code='CODE'" as '&#CODE;' or {{%htmlsym alias='ALIAS'}} as '&ALIAS;'.
     * @param array $params
     * @return string result string or '' on any error
     */
    protected static function pluginHtmlsym($params)
    {
        if (!empty($params['code'])) {
            return "&#{$params['code']};";
        } elseif (!empty($params['alias'])) {
            return "&{$params['alias']};";
        } else {
            return '';
        }
    }
}
