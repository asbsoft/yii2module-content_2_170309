<?php

$mgr = Yii::$app->urlManager;
$normalizeTrailingSlash = !empty($mgr->normalizer->normalizeTrailingSlash) && $mgr->normalizer->normalizeTrailingSlash;//var_dump($normalizeTrailingSlash);

// route without prefix => controller/action without current (and parent) module(s) IDs
$result = [
  //'<action:(view)>/<id:\d+>'    => 'main/<action>',
  //'<action:(list)>/<page:\d+>'  => 'main/<action>',
  //'<action:(index|list)>'       => 'main/<action>',
  //'<path:[a-z0-9/-]+>'          => 'main/view',
  //'?'                           => 'main/index', //?? preg_match(): Compilation failed: nothing to repeat at offset 1
//  ''                            => 'main/index', 
];

if ($normalizeTrailingSlash) {
    $result[''] = 'main/index';
} else {
    $result['?'] = 'main/index';
}//var_dump($result);exit;

return $result;
