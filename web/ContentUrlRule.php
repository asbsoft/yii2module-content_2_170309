<?php

namespace asb\yii2\modules\content_2_170309\web;

use asb\yii2\modules\content_2_170309\models\Content;

use asb\yii2\common_2_170212\web\UniUrlRuleInterface;
use asb\yii2\common_2_170212\i18n\LangHelper;

use Yii;
use yii\web\UrlRule as YiiWebUrlRule;

class ContentUrlRule extends YiiWebUrlRule implements UniUrlRuleInterface
{
    public $contentId;

    /**
     * @inheritdoc
     */
    public function showRouteInfo()
    {
        $result = ''
            . "'{$this->pattern}' => '{$this->route}'"
            . ", contentId = '{$this->contentId}'\n"
            ;
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function parseRequest($manager, $request)
    {//echo __METHOD__;var_dump($request->pathInfo);
        if (!empty($manager->normalizer->normalizeTrailingSlash) && $manager->normalizer->normalizeTrailingSlash) {
            $request->pathInfo = rtrim($request->pathInfo, '/');
        }
        $result = parent::parseRequest($manager, $request);//echo __METHOD__;var_dump($result);
        if (is_array($result)) {
            list($route, $params) = $result;
            $params['id'] = $this->contentId;
            $result = [$route, $params];//echo __METHOD__."({$request->pathInfo}):FOUND:";var_dump($result);exit;
        }
        return $result;
    }

    /**
     * @inheritdoc
     * There are manu rules with same route (action unique id '.../main/view') but with different $this->contentId.
     * System find first, but need to pass processing to proper rule.
     * @return string|false the created URL, or false if this rule cannot be used for creating this URL.
     */
    public function createUrl($manager, $route, $params)
    {//echo __METHOD__."(mgr,'$route')";var_dump($params);echo"pattern:{$this->pattern},contId:{$this->contentId}<br>";
        $lang = empty($params['lang']) ? Yii::$app->language : $params['lang'];
        $lang = LangHelper::normalizeLangCode($lang);

        if (empty($params['id'])) $params['id'] = 0; // root is default

        //$rule = $this;
        $rule = null;
        // find proper rule with contentId == $params['id']:
        foreach ($manager->rules as $nextRule) {
            if (!empty($nextRule->route) && $nextRule->route == $route && $nextRule->contentId == $params['id']) {
                $rule = $nextRule;
                break;
            }
        }//echo"FOUND:pattern:{$rule->pattern},contId:{$rule->contentId},lang:{$lang}<br>";

        if (empty($rule)) return false; // if not found proper

        $node = Content::node($rule->contentId);
        if (empty($node->i18n[$lang]->text)) {
            //$result = $manager->createUrl(Yii::$app->defaultRoute); //?? may be inf loop
            $result = ''; // use as default
        } else {//var_dump($node->i18n[$lang]->text);
            $result = substr($rule->pattern, 2, -3);
        }//echo'result:';var_dump($result);//exit;
        return $result;
    }

}
