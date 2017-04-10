<?php

namespace asb\yii2\modules\content_2_170309\widgets;

use asb\yii2\modules\content_2_170309\Module;

use Yii;
use yii\base\Widget;

use Exception;

class ContentWidget extends Widget
{
    /** Content id: numeric or path */
    public $id;

    /** Language code or null for default */
    public $lang;

    /** Parameter array */
    public $params = [];
    
    /**
     * @inheritdoc
     */
    public function run()
    {//echo __METHOD__;var_dump($this->params);exit;

        $module = Module::getModuleByClassname(Module::className());
        if (empty($module)) {
            $msg = "Can't load own module in " . __METHOD__;
            if (YII_DEBUG) throw new Exception($msg);
            Yii::error($msg);
            return '';
        }

        $renderAction = "{$module->uniqueId}/main/render";
        return Yii::$app->runAction($renderAction, [
            'id' => $this->id,
            'lang' => $this->lang,
            'params' => serialize($this->params),
        ]);
    }
}
