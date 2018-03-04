<?php

use asb\yii2\modules\content_2_170309\models\ContentBase;
use asb\yii2\modules\content_2_170309\models\ContentI18n;

use asb\yii2\common_2_170212\behaviors\ParamsAccessBehaviour;


$filesSubpath = 'content';

return [
    'label'   => 'Content manager',
    'version' => '2.170309',
    'origin'  => 'content_2_170309 @ common_2_170212',

    // Use external start page from application defaultRoute if true,
    // if FALSE then as start page will use first content page with root = 0
    'useExternalStartPage' => false, // FALSE - default: will use content start page
  //'useExternalStartPage' => true, // will use Yii::$app->defaultRoute for render start page

    'filesSubpath'        => $filesSubpath,
    'uploadsContentDir'   => "@uploadspath/{$filesSubpath}",
    'uploadsContentUrl'   => "@webfilesurl/{$filesSubpath}",

    'behaviors' => [
        'params-access' => [
            'class' => ParamsAccessBehaviour::className(),
            'defaultRole' => 'roleAdmin',
            'readonlyParams' => [
                 'filesSubpath',
                 'uploadsContentDir',
                 'uploadsContentUrl',
            ],
        ],
    ],

    // Maximum image size in bytes
    'maxImageSize' => 102400,

    // Admin list page size
    'pageSizeAdmin' => 10,

    // Minimum required length of data
    'slugMinLength'  => 2, //symbols
    'titleMinLength' => 3, //symbols
    'textMinLength'  => 10, //symbols - minimal length of article

    // Indicate when article published (is_visible = true) author can't edit it
    'canAuthorEditOwnVisibleArticle' => false,
  //'canAuthorEditOwnVisibleArticle' => true,

    // Set TRUE to show in edit form all registered languages, not only visible
  //'editAllLanguages' => false,
    'editAllLanguages' => true,

    // Show detail SQL-errors diagnostic if appear
    'showAdminSqlErrors' => true,
  //'showAdminSqlErrors' => false,

    // Tables names
    ContentBase::className() => [
        'tableName' => '{{%content}}',
    ],
    ContentI18n::className() => [
        'tableName' => '{{%content_i18n}}',
    ],

];
