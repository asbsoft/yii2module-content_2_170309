<?php

// route without prefix => controller/action without current (and parent) module(s) IDs
$result = [
    '<action:(view|update|delete|change-visible)>/<id:\d+>' => 'admin/<action>',

    'shift/<direction:(up|down)>/<id:\d+>'                  => 'admin/shift',

    'index/<parent:[-]>/<page:\d+>'                         => 'admin/index',
    'index/<parent:\d+>/<page:\d+>'                         => 'admin/index',

  //'<action:(index|create)/<parent:\d+>'                   => 'admin/<action>', //?? not work
    'index/<parent:[-]>'                                    => 'admin/index',
    'index/<parent:\d+>'                                    => 'admin/index',
    'create/<parent:\d+>'                                   => 'admin/create',

  //'show-tree/<active:\d+>'                                => 'admin/show-tree', // for runAction only

    'el-finder/<action:(connect|manager)>/<id:\d+>'         => 'el-finder/<action>',
    '<action:(index|create|check-route)>'                   => 'admin/<action>',

  //'?'                                                     => 'admin/index', //!! no '' - never start routes from root
];

$mgr = Yii::$app->urlManager;
$normalizeTrailingSlash = !empty($mgr->normalizer->normalizeTrailingSlash) && $mgr->normalizer->normalizeTrailingSlash;
if ($normalizeTrailingSlash) {
    $result[''] = 'admin/index';
} else {
    $result['?'] = 'admin/index';
}

return $result;
