<?php

use asb\yii2\modules\content_2_170309\models\ContentBase;
use asb\yii2\modules\content_2_170309\models\ContentI18n;

$filesSubpath = 'content';

return [
    'label'   => 'Content manager',
    'version' => '2.170309',
    'origin'  => 'content_2_170309 @ common_2_170212',

    'filesSubpath'        => $filesSubpath,
    'uploadsContentDir'   => "@uploadspath/{$filesSubpath}",
    'uploadsContentUrl'   => "@webfilesurl/{$filesSubpath}",

    'maxImageSize' => 102400, //bytes

    //lists oage size
    'pageSizeAdmin' => 10,

    'slugMinLength'  => 2, //symbols
    'titleMinLength' => 3, //symbols
    'textMinLength'  => 10, //symbols - minimal length of article

    // indicate when article published (is_visible = true) author can't edit it
    'canAuthorEditOwnVisibleArticle' => false,
  //'canAuthorEditOwnVisibleArticle' => true,

    // set TRUE to show in edit form all registered languages, not only visible
    'editAllLanguages' => false,
  //'editAllLanguages' => true,

    'showAdminSqlErrors' => true,
  //'showAdminSqlErrors' => false,

    ContentBase::className() => [
        'tableName' => '{{%content}}',
    ],
    ContentI18n::className() => [
        'tableName' => '{{%content_i18n}}',
    ],

];
