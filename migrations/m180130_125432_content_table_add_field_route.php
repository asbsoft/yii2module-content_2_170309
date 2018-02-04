<?php

use asb\yii2\modules\content_2_170309\Module;
use asb\yii2\modules\content_2_170309\models\Content;

use yii\db\Migration;

/**
 * @author ASB <ab2014box@gmail.com>
 */
class m180130_125432_content_table_add_field_route extends Migration
{
    protected $tableName;

    public function init()
    {
        parent::init();

        $this->tableName = Content::tableName();
    }
    
    public function safeUp()
    {
        $this->addColumn($this->tableName, 'route', $this->string(255));
    }

    public function safeDown()
    {
        //echo basename(__FILE__, '.php') . " cannot be reverted.\n";
        //return false;
        $this->dropColumn($this->tableName, 'route');
    }

}
