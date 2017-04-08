<?php

namespace asb\yii2\modules\content_2_170309\models;

use asb\yii2\modules\content_2_170309\Module;

use asb\yii2\common_2_170212\models\DataModel;
//use asb\yii2\common_2_170212\validators\EitherValidator;

use Yii;

/**
 * This is the model class for table "{{%content_i18n}}".
 *
 * @property integer $id
 * @property integer $content_id
 * @property string $lang_code
 * @property string $title
 * @property string $text
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class ContentI18n extends DataModel
{
    const TABLE_NAME = 'content_i18n';

    public $titleMinLength = 10;  //symbols
    public $textMinLength  = 100; //symbols - minimal length of article

    protected $contentHelper;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->contentHelper = new $this->module->contentHelper;

        if (empty($this->module)) {
            $this->module = Module::getModuleByClassname(Module::className());
        }

        if (!empty($this->module->params['titleMinLength']) && intval($this->module->params['titleMinLength']) > 0) {
            $this->titleMinLength = intval($this->module->params['titleMinLength']);
        }

        if (!empty($this->module->params['textMinLength']) && intval($this->module->params['textMinLength']) > 0) {
            $this->textMinLength = intval($this->module->params['textMinLength']);
        }
    
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // Joined primary key value among i18n-attributes can't set before create main record.
            // Unset it to avoid validation error. This attribute will (re)set in mainModel->save().
          //['content_id', 'integer'],
          //['content_id', 'required'],
            ['content_id', 'safe'],

            ['lang_code', 'string', 'max' => 5],

            ['title', 'trim'],
            ['title', 'string', 'min' => $this->titleMinLength, 'max' => 255],
            ['text', 'string', 'min' => $this->textMinLength],

           //[['title', 'text'], EitherValidator::className()],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t($this->tcModule, 'ID'),
            'content_id' => Yii::t($this->tcModule, 'Content ID'),
            'lang_code' => Yii::t($this->tcModule, 'Lang Code'),
            'title' => Yii::t($this->tcModule, 'Menu item / Title'),
            'text' => Yii::t($this->tcModule, 'Text'),
        ];
    }

    /**
     * @inheritdoc
     * @return ContentI18nQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ContentI18nQuery(get_called_class());
    }

    /**
     * Check if model has data to save.
     * @return boolean
     */
    public function hasData()
    {
        return !empty($this->title) || !empty($this->text);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->text = $this->contentHelper->beforeSaveBody($this->text);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Correct text field(s) after select from database.
     */
    public function correctSelectedText()
    {
        $this->text = $this->contentHelper->afterSelectBody($this->text);
    }

}
