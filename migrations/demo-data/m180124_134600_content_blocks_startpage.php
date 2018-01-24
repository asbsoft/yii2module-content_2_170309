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
class m180124_134600_content_blocks_startpage extends Migration
{
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
        $this->insert($this->tableName, [
            'parent_id'   => 0,
            'slug'        => 'startpage-blocks',
            'prio'        => 1,
            'is_visible'  => false,  // it's not webpage, it is container of text-blocks
            'owner_id'    => $this->adminUserId,
            'create_time' => new Expression('NOW()'),
        ]);
        $blockContainerId = $this->db->getLastInsertID();

        $prio = 1;

        $name = 'block-welcome';
        $this->insert($this->tableName, [
            'parent_id'   => $blockContainerId,
            'slug'        => $name,
            'prio'        => $prio++,
            'is_visible'  => true,
            'owner_id'    => $this->adminUserId,
            'create_time' => new Expression('NOW()'),
        ]);
        $contentId = $this->db->getLastInsertID();
        foreach ($this->languages as $language) {
            $addText = ($language->code2 == 'ru' || $language->code2 == 'uk') ? $this->lorem['cyr'] : $this->lorem['eng'];
            $this->insert($this->tableNameI18n, [
                'content_id' => $contentId,
                'lang_code'  => $language->code_full,
                'title'      => $name . ' in ' . $language->name_en,
                'text'       => ''
                            //. "<h2>Block welcome in {$language->name_en} ({$language->name_orig})</h2>"
                              . "<p>Some text in {$language->name_orig}...</p>"
                              . "<p>Change it to your original text.</p>"
                              . str_repeat("<p>{$addText['long']}</p>", 5)
                              . "<p>Some text in {$language->name_orig}...</p>"
            ]);
        }

        $name = 'block-features';
        $this->insert($this->tableName, [
            'parent_id'   => $blockContainerId,
            'slug'        => $name,
            'prio'        => $prio++,
            'is_visible'  => true,
            'owner_id'    => $this->adminUserId,
            'create_time' => new Expression('NOW()'),
        ]);
        $contentId = $this->db->getLastInsertID();
        foreach ($this->languages as $language) {
            $addText = ($language->code2 == 'ru' || $language->code2 == 'uk') ? $this->lorem['cyr'] : $this->lorem['eng'];
            $this->insert($this->tableNameI18n, [
                'content_id' => $contentId,
                'lang_code'  => $language->code_full,
                'title'      => $name . ' in ' . $language->name_en,
                'text'       => ''
                            //. "<h2>Block features in {$language->name_en} ({$language->name_orig})</h2>"
                              . "<p>Some text in {$language->name_orig}...</p>"
                              . "<p>Change it to your original text.</p>"
                              . str_repeat("<p>{$addText['long']}</p>", 4)
                              . "<p>Some text in {$language->name_orig}...</p>"
            ]);
        }

        $name = 'block-features-multilang';
        $this->insert($this->tableName, [
            'parent_id'   => $blockContainerId,
            'slug'        => $name,
            'prio'        => $prio++,
            'is_visible'  => true,
            'owner_id'    => $this->adminUserId,
            'create_time' => new Expression('NOW()'),
        ]);
        $contentId = $this->db->getLastInsertID();
        foreach ($this->languages as $language) {
            $addText = ($language->code2 == 'ru' || $language->code2 == 'uk') ? $this->lorem['cyr'] : $this->lorem['eng'];
            $this->insert($this->tableNameI18n, [
                'content_id' => $contentId,
                'lang_code'  => $language->code_full,
                'title'      => $name . ' in ' . $language->name_en,
                'text'       => ''
                              . "<p>Some text in {$language->name_orig}...</p>"
                              . "<p>Change it to your original text.</p>"
                              . "<p>{$addText['short']}</p>"
                              . "<p>Some text in {$language->name_orig}...</p>"
            ]);
        }
    
        $name = 'block-news';
        $this->insert($this->tableName, [
            'parent_id'   => $blockContainerId,
            'slug'        => $name,
            'prio'        => $prio++,
            'is_visible'  => true,
            'owner_id'    => $this->adminUserId,
            'create_time' => new Expression('NOW()'),
        ]);
        $contentId = $this->db->getLastInsertID();
        foreach ($this->languages as $language) {
            $addText = ($language->code2 == 'ru' || $language->code2 == 'uk') ? $this->lorem['cyr'] : $this->lorem['eng'];
            $this->insert($this->tableNameI18n, [
                'content_id' => $contentId,
                'lang_code'  => $language->code_full,
                'title'      => $name . ' in ' . $language->name_en,
                'text'       => ''
                            //. "<h2>Block news in {$language->name_en} ({$language->name_orig})</h2>"
                              . "<p>Some text in {$language->name_orig}...</p>"
                              . "<p>Change it to your original text.</p>"
                              . str_repeat("<p>{$addText['long']}</p>", 2)
                              . "<p>Some text in {$language->name_orig}...</p>"
            ]);
        }
    
        $name = 'block-contacts';
        $this->insert($this->tableName, [
            'parent_id'   => $blockContainerId,
            'slug'        => $name,
            'prio'        => $prio++,
            'is_visible'  => true,
            'owner_id'    => $this->adminUserId,
            'create_time' => new Expression('NOW()'),
        ]);
        $contentId = $this->db->getLastInsertID();
        foreach ($this->languages as $language) {
            $addText = ($language->code2 == 'ru' || $language->code2 == 'uk') ? $this->lorem['cyr'] : $this->lorem['eng'];
            $this->insert($this->tableNameI18n, [
                'content_id' => $contentId,
                'lang_code'  => $language->code_full,
                'title'      => $name . ' in ' . $language->name_en,
                'text'       => ''
                            //. "<h2>Block contacts in {$language->name_en} ({$language->name_orig})</h2>"
                              . "<p>Some text in {$language->name_orig}...</p>"
                              . "<p>Change it to your original text.</p>"
                              . str_repeat("<p>{$addText['long']}</p>", 1)
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
