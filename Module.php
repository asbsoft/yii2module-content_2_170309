<?php

namespace asb\yii2\modules\content_2_170309;

use asb\yii2\modules\content_2_170309\models\ContentRoutesBootstrap;
use asb\yii2\common_2_170212\base\UniModule;
use asb\yii2\common_2_170212\web\RoutesInfo;

use yii\base\Application;

/**
 * Module class.
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class Module extends UniModule
{
    // Action for processing content pages
    const ACTION_DEF_ROUTE = 'main/view';

    // Saved system default route
    public static $savedDefaultRoute;
    
    // Additional members - shared outside classes init from config
    public $userIdentity;
    public $langHelper;
    public $contentHelper;

    public function bootstrap($app)
    {
        parent::bootstrap($app);

        if (empty(static::$savedDefaultRoute)) {
            static::$savedDefaultRoute = $app->defaultRoute;
        }

        $disableFrontRoutes = isset($this->routesConfig['main']) && $this->routesConfig['main'] == false;

        if (!$disableFrontRoutes) {
            $app->defaultRoute = $this->uniqueId . '/' . self::ACTION_DEF_ROUTE;

            $app->on(Application::EVENT_BEFORE_REQUEST, function($event) use($app) {
                //$routes = RoutesInfo::showRoutes();echo"before:<pre>$routes</pre>";
                $routesModel = new ContentRoutesBootstrap;
                $routesModel->moduleUid = $this->uniqueId;
                $routes = $routesModel->getRoutes();
                $app->urlManager->addRules($routes);
                //$routes = RoutesInfo::showRoutes();echo"after:<pre>$routes</pre>";
            });
        }
    }

}
