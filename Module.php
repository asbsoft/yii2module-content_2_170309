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
    // Additional members - shared outside classes init from config
    public $userIdentity;
    public $langHelper;
    public $contentHelper;

    public function bootstrap($app)
    {
        parent::bootstrap($app);

        $disableFrontRoutes = isset($this->routesConfig['main']) && $this->routesConfig['main'] == false;//var_dump($disableFrontRoutes);var_dump($this->routesConfig);exit;
        if (!$disableFrontRoutes) {
            $app->defaultRoute = $this->uniqueId . '/main/view'; // new default route

            $app->on(Application::EVENT_BEFORE_REQUEST, function($event) use($app) {//echo __METHOD__;var_dump($event->name);
                //$routes = RoutesInfo::showRoutes();echo"before:<pre>$routes</pre>";
                $routesModel = new ContentRoutesBootstrap;
                $routesModel->moduleUid = $this->uniqueId;
                $routes = $routesModel->getRoutes();//var_dump($routes);
                $app->urlManager->addRules($routes);//$routes = RoutesInfo::showRoutes();echo"after:<pre>$routes</pre>";
            });
        }
    }

}
