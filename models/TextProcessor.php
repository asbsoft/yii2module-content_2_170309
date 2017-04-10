<?php

namespace asb\yii2\modules\content_2_170309\models;

use Yii;
use yii\helpers\Url;

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
    {//echo __METHOD__;var_dump($params);//var_dump($text);
        // translate '{{PARAM}}'
        $trtab = [];
        if (is_array($params)) {
            foreach ((array)$params as $name => $value) {
                $trtab['{{' . $name . '}}'] = $value;
            }
            $text = strtr($text, $trtab);
        }

        // find and process in text '{{%plugin ...params...}}'
        $text = preg_replace_callback('/{{%([^}]+)}}/', 'static::runPlugin', $text);//var_dump($text);
        
        // erase other '{{...}}'
        $text = preg_replace('/{{[^}]*}}/', '', $text);//var_dump($text);
        return $text;
    }

    protected static function runPlugin($matches)
    {//echo __METHOD__;var_dump($matches);
         $origText = $matches[0];
         $cmd = $matches[1];
         $cmd = html_entity_decode($cmd, ENT_QUOTES);//echo'cmd:';var_dump($cmd);

         $parts = explode(' ', $cmd, 2);
         $plugin = trim($parts[0]);
         $rest = empty($parts[1]) ? '' : trim($parts[1]);//echo"plugin='$plugin', rest='$rest'<br>";

         $parts = explode(',', $rest);//echo'parts:';var_dump($parts);
         $params = [];
         foreach ($parts as $next) {
             $next = trim($next);
             if (empty($next)) continue;

             if (preg_match('/([^=]+)=?(.*)/', $next, $found)) {//var_dump($found);
                 $name = trim($found[1]);
                 $val = trim($found[2]);
                 $val = trim($val, "'");
                 $val = trim($val, '"');//echo "'{$name}'=>'$val'<br>";
                 if (empty($val)) {
                     $params[] = $name;
                 } else {
                     $params[$name] = $val;
                 }
             }
         }//echo"plugin='$plugin'";var_dump($params);

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
     * Processing "{{%url route='...', paramN='...',}]" as Url::toRoute()
     * or "{{%url url='...', paramN='...',}]" as Url::to()
     * @param array $params
     * @return string result text or '' on any error
     */
    protected static function pluginUrl($params)
    {//echo __METHOD__;var_dump($params);
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
     * Processing "{{%widget name='...', paramN='...',}]"
     * @param array $params
     * @return string result string or '' on any error
     */
    protected static function pluginWidget($params)
    {//echo __METHOD__;var_dump($params);
        $result = '';
        if (!empty($params['name'])) {
            $name = $params['name'];
            unset($params['name']);
            if (isset(static::$widgets[$name])) {
                $widgetParams = $params;
                $widgetParams['class'] = static::$widgets[$name];//var_dump($widgetParams);exit;
                $widget = Yii::createObject($widgetParams);
                $result = $widget->run();
            } else {
                $msg = __METHOD__ . ": unknown widget '{{%widget name=$name }}'. Real params is " . var_export($params, true);
                Yii::error($msg);
            }
        } else {
            $msg = __METHOD__ . ": '{{%widget}}' must have 'name' parameter. Real params is " . var_export($params, true);
            Yii::error($msg);
        }
        return $result;
    }

}
