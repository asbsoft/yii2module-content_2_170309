<?php

use asb\yii2\common_2_170212\i18n\LangHelper;
use asb\yii2\modules\content_2_170309\models\Content;
use asb\yii2\modules\content_2_170309\models\ContentI18n;

use yii\db\Migration;
use yii\db\Expression;

//Yii::setAlias('@asb/yii2/modules', '@vendor/asbsoft/yii2modules');

/**
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class m170411_134500_content_startpage extends Migration
{
    protected $adminUserId = 100; //!! tune

    protected $tableName;
    protected $tableNameI18n;

    protected $languages;

    public function init()
    {
        parent::init();

        $this->tableName     = Content::tableName();
        $this->tableNameI18n = ContentI18n::tableName();

        $this->languages = LangHelper::activeLanguages();
    }

    public function safeUp()
    {
        $now = new Expression('NOW()');
        $this->insert($this->tableName, [
            'parent_id'   => 0,
            'slug'        => 'home',
            'prio'        => 1,
            'is_visible'  => true,
            'owner_id'    => $this->adminUserId,
            'create_time' => $now,
        ]);
        $contentId = $this->db->getLastInsertID();

        foreach ($this->languages as $language) {
            $this->insert($this->tableNameI18n, [
                'content_id' => $contentId,
                'lang_code'  => $language->code_full,
                'title'      => 'Start page in ' . $language->name_en,
                'text'       => "<h1>Start page title in {$language->name_en} </h1>"
                              . "<h2>({$language->name_orig})</h2>"
                              . "<p>Hello world!</p><p>Change this to original text.</p>"
                              . str_repeat("<p>Some text in {$language->name_orig}...</p>", 10),
            ]);
        }
    }

    public function safeDown()
    {
        echo basename(__FILE__, '.php') . " cannot be reverted.\n";
        return false;
    }
}
