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
class m180124_134501_content_page_intro extends Migration
{
    protected $prio = 20;  // check to not equal to such prio in another migrations

    protected $adminUserId;

    protected $tableName;
    protected $tableNameI18n;

    protected $languages;

    protected $lorem;

    public function init()
    {
        parent::init();

        $config = require(__DIR__ . '/_config.php');
        $this->adminUserId = $config['adminUserId'];
        $this->lorem = $config['lorem'];

        $this->tableName     = Content::tableName();
        $this->tableNameI18n = ContentI18n::tableName();

        $this->languages = LangHelper::activeLanguages();
    }

    public function safeUp()
    {
        $now = new Expression('NOW()');
        $this->insert($this->tableName, [
            'parent_id'   => 0,
            'slug'        => 'intro',
            'prio'        => $this->prio,
            'is_visible'  => true,
            'owner_id'    => $this->adminUserId,
            'create_time' => $now,
        ]);
        $contentId = $this->db->getLastInsertID();

        foreach ($this->languages as $language) {
            $addText = ($language->code2 == 'ru' || $language->code2 == 'uk') ? $this->lorem['cyr'] : $this->lorem['eng'];
            $this->insert($this->tableNameI18n, [
                'content_id' => $contentId,
                'lang_code'  => $language->code_full,
                'title'      => $language->name_en . ' introduction',
                'text'       => "<h1>Introduction in {$language->name_en} ({$language->name_orig})</h1>"
                              . "<p>Some text in {$language->name_orig}...</p>"
                              . "<p>Change it to your original text.</p>"
                              . "<p>{$addText['short']}</p>"
                              . str_repeat("<p>{$addText['long']}</p>", 5)
                              . "<p>Some text in {$language->name_orig}...</p>"
            ]);
        }
    }

    public function safeDown()
    {
        echo basename(__FILE__, '.php') . " cannot be reverted.\n";
        return false;
    }
}
