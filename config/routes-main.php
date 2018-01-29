<?php
// route without prefix => controller/action without current (and parent) module(s) IDs
$result = [
  // routes in this module will add dynamicly
  //'<action:(view)>/<id:\d+>'    => 'main/<action>',
  //'<path:[a-z0-9/-]+>'          => 'main/view',
    'show-<id:\d+>/<slug:[a-z0-9/-]+>' => 'main/show',
];

/*
$mgr = Yii::$app->urlManager;
$normalizeTrailingSlash = !empty($mgr->normalizer->normalizeTrailingSlash) && $mgr->normalizer->normalizeTrailingSlash;
if ($normalizeTrailingSlash) {
    $result[''] = 'main/index';
} else {
    $result['?'] = 'main/index';
}
*/

return $result;
