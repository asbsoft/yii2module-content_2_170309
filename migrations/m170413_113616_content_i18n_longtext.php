<?php

use asb\yii2\modules\content_2_170309\models\ContentI18n;

use yii\db\Migration;

class m170413_113616_content_i18n_longtext extends Migration
{
    protected $tableName;

    public function init()
    {
        parent::init();

        $this->tableName = $this->db->schema->getRawTableName(ContentI18n::tableName());
    }

    public function safeUp()
    {
        if ($this->db->driverName === 'mysql') {
            $sql = "ALTER TABLE `{$this->tableName}` CHANGE `text` `text` LONGTEXT";
            $this->execute($sql);
        }
    }

    public function safeDown()
    {
        echo basename(__FILE__, '.php') . " cannot be reverted.\n";
        return false;
    }
}
