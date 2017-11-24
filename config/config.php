<?php

use asb\yii2\common_2_170212\base\UniApplication;
use asb\yii2\common_2_170212\i18n\LangHelper;
use asb\yii2\common_2_170212\helpers\EditorContentHelper;

$adminUrlPrefix = empty(Yii::$app->params['adminPath']) ? '' : Yii::$app->params['adminPath'] . '/';//var_dump($adminUrlPrefix);

$type = empty(Yii::$app->type) ? false : Yii::$app->type;//var_dump($type);exit;

return [
    //'layoutPath' => '@asb/yii2cms/modules/sys/views/layouts',
    'layouts' => [ // (module/application) type => basename
        'frontend' => 'layout_main',
        'backend'  => 'layout_admin',
    ],

    // External using classes
    'userIdentity'  => Yii::$app->user->identityClass,
    'langHelper'    => LangHelper::className(),
    'contentHelper' => EditorContentHelper::className(),

    /** Shared widgets */
    'widgets' => [ // alias => class name or object array
        'content' => 'asb\yii2\modules\content_2_170309\widgets\ContentWidget',
    ],

    /** Shared inherited models */
    'models' => [ // alias => class name or object array
        'Content'            => 'asb\yii2\modules\content_2_170309\models\Content',
        'ContentQuery'       => 'asb\yii2\modules\content_2_170309\models\ContentQuery',
        'ContentI18n'        => 'asb\yii2\modules\content_2_170309\models\ContentI18n',
        'ContentSearch'      => 'asb\yii2\modules\content_2_170309\models\ContentSearch',
        'ContentMenuBuilder' => 'asb\yii2\modules\content_2_170309\models\ContentMenuBuilder',
    ],

    /** Inherited asset(s) */
    'assets' => [ // alias => class name
        'AdminAsset' => 'asb\yii2\modules\content_2_170309\assets\AdminAsset',
    ],

    /** Routes config(s) */
    'routesConfig' => [ // default: type => prefix|[config]
/*
        'main'  => $type == UniApplication::APP_TYPE_BACKEND  ? false : [
            'urlPrefix' => '',
            'append' => true,
        ],
*/
        'admin' => $type == UniApplication::APP_TYPE_FRONTEND ? false : [
            'urlPrefix' => $adminUrlPrefix . 'content',
            'startLink' => [
                'label' => 'Content manager', //!! no translate here, it will translate using 'MODULE_UID/module' tr-category
              //'link'  => '', // default
                'action' => 'admin/index',
            ],
        ],
    ],

];
