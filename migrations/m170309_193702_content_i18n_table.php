<?php

use asb\yii2\modules\content_2_170309\Module;
use asb\yii2\modules\content_2_170309\models\ContentI18n;

use yii\db\Migration;

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class m170309_193702_content_i18n_table extends Migration
{
    protected $tableName;
    protected $idxNamePrefix;

    public function init()
    {
        parent::init();

        // if problems with autoload (classes not found):
        //Yii::setAlias('@asb/yii2', dirname(dirname(dirname(__DIR__))) . '/yii2-common_2_170212');
        //Yii::setAlias('@asb/yii2/modules/content_2_170309', dirname(__DIR__));//var_dump(Yii::$aliases);exit;

        $this->tableName     = ContentI18n::tableName();
        $this->idxNamePrefix = 'idx-' . ContentI18n::baseTableName();
    }
    
    public function safeUp()
    {
        $tableOptions = null;

        $this->createTable($this->tableName, [
            'id' => $this->primaryKey(),
            'content_id' => $this->integer()->notNull(),
            'lang_code' => $this->string(5)->notNull(),
            'title' => $this->string(255),
            'text' => $this->text(),
        ], $tableOptions);
        $this->createIndex("{$this->idxNamePrefix}-content-id",  $this->tableName, 'content_id');
    }

    public function safeDown()
    {
        //echo basename(__FILE__, '.php') . " cannot be reverted.\n";
        //return false;

        $this->dropTable($this->tableName);
    }

}
