<?php

use asb\yii2\modules\content_2_170309\Module;
use asb\yii2\modules\content_2_170309\models\ContentI18n;
use asb\yii2\modules\content_2_170309\models\Content;

use yii\db\Migration;

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class m170309_193702_content_i18n_table extends Migration
{
    protected $tableNameI18n;
    protected $tableName;
    protected $idxNamePrefix;
    protected $fkName;

    public function init()
    {
        parent::init();

        $this->tableNameI18n = ContentI18n::tableName();
        $this->tableName     = Content::tableName();
        $this->idxNamePrefix = 'idx-' . ContentI18n::basetableName();
        $this->fkName        = 'fk_' . ContentI18n::basetableName();
    }
    
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        $this->createTable($this->tableNameI18n, [
            'id' => $this->primaryKey(),
            'content_id' => $this->integer()->notNull(),
            'lang_code' => $this->string(5)->notNull(),
            'title' => $this->string(255),
            'text' => $this->text(),
        ], $tableOptions);
        $this->createIndex("{$this->idxNamePrefix}-content-id",  $this->tableNameI18n, 'content_id');
        $this->addForeignKey($this->fkName, $this->tableNameI18n, 'content_id', $this->tableName, 'id', 'CASCADE', 'RESTRICT');
    }

    public function safeDown()
    {
        //echo basename(__FILE__, '.php') . " cannot be reverted.\n";
        //return false;

        $this->dropForeignKey("fk_{$this->tableNameI18n}", $this->tableNameI18n);
        $this->dropTable($this->tableNameI18n);
    }

}
