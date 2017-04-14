<?php

use asb\yii2\modules\content_2_170309\Module;
use asb\yii2\modules\content_2_170309\models\Content;

use yii\db\Migration;

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class m170309_193701_content_table extends Migration
{
    protected $tableName;
    protected $idxNamePrefix;

    public function init()
    {
        parent::init();

        $this->tableName     = Content::tableName();
        $this->idxNamePrefix = 'idx-' . Content::baseTableName();
    }
    
    public function safeUp()
    {
        $tableOptions = null;

        $this->createTable($this->tableName, [
            'id'          => $this->primaryKey(),
            'parent_id'   => $this->integer()->notNull()->defaultValue(0),
            'slug'        => $this->string(255),
            'prio'        => $this->integer()->notNull()->defaultValue(0),
            'is_visible'  => $this->boolean()->notNull()->defaultValue(false),
            'owner_id'    => $this->integer(),
            'layout'      => $this->string(255),
            'create_time' => $this->datetime()->notNull(),
            'update_time' => $this->timestamp(),
        ], $tableOptions);
        $this->createIndex("{$this->idxNamePrefix}-parent-id", $this->tableName, 'parent_id');
        $this->createIndex("{$this->idxNamePrefix}-slug",      $this->tableName, 'slug');
        $this->createIndex("{$this->idxNamePrefix}-owner-id",  $this->tableName, 'owner_id');
        $this->createIndex("{$this->idxNamePrefix}-visible",   $this->tableName, 'is_visible');
    }

    public function safeDown()
    {
        //echo basename(__FILE__, '.php') . " cannot be reverted.\n";
        //return false;

        $this->dropTable($this->tableName);
    }

}
