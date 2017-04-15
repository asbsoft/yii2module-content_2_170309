<?php

$mgr = Yii::$app->urlManager;
$normalizeTrailingSlash = !empty($mgr->normalizer->normalizeTrailingSlash) && $mgr->normalizer->normalizeTrailingSlash;//var_dump($normalizeTrailingSlash);

// route without prefix => controller/action without current (and parent) module(s) IDs
$result = [
  // routes in this module will add dynamicly
  //'<action:(view)>/<id:\d+>'    => 'main/<action>',
  //'<path:[a-z0-9/-]+>'          => 'main/view',
];

if ($normalizeTrailingSlash) {
    $result[''] = 'main/index';
} else {
    $result['?'] = 'main/index';
}//var_dump($result);exit;

return $result;
