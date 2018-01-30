<?php

namespace asb\yii2\modules\content_2_170309\models;

use asb\yii2\modules\content_2_170309\Module;
use asb\yii2\modules\content_2_170309\models\ContentSearch;

use asb\yii2\common_2_170212\models\DataModel;

use Yii;
use yii\db\Expression;
use yii\helpers\FileHelper;

use Exception;

/**
 * This is the model class for table "{{%content}}".
 * 
 * Content data has tree hierarchy provided by 'parent_id' property.
 * Every node must have 'slug' property unique for nodes with same 'parent_id'.
 * Every node can have multilang property 'title' use as menu item
 * (and sometimes can use as text title if text have '{{title}}' fragment).
 * If node have multilang 'text' property and all true 'is_visible' properties from itself to root,
 * it will get own route and can show as web-page with address makes by concatenate slugs from root to itself,
 *
 * @property integer $id
 * @property integer $parent_id
 * @property string  $slug
 * @property string  $route
 * @property integer $is_visible
 * @property integer $owner_id
 * @property string  $create_time
 * @property string  $update_time
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class ContentBase extends DataModel
{
    //const TABLE_NAME = 'content'; // deprecated

    const I18N_JOIN_MODEL = 'ContentI18n';
    const I18N_JOIN_PRIM_KEY = 'content_id';

    const AFTERSAVE_LIST = 'list';

    /** Nodes info caching */
    public static $caching = true;
    
    public $slugMinLength = 5; //symbols

    /** Default order in list */
    public static $defaultOrderBy = ['prio' => SORT_ASC]; //!! don't change here

    /** Items in list */
    public $pageSizeAdmin = 10; // default if not reset in params

    /** For select after save go to view or list */
    public $aftersave;

    // multilang properties
    public $title;
    public $text;

    /** Full slugs chain */
    public $nodePath;

    public $langHelper;
    public $languages;
    public $langCodeMain;

    /** When validation detect error in i18n-model it save leng code of error here */
    public $errorLang;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->module)) {
            $this->module = Module::getModuleByClassname(Module::className());
        }

        $param = 'slugMinLength';
        if (!empty($this->module->params[$param]) && intval($this->module->params[$param]) > 0) {
            $this->$param = intval($this->module->params[$param]);
        }

        $param = 'pageSizeAdmin';
        if (!empty($this->module->params[$param]) && intval($this->module->params[$param]) > 0) {
            $this->$param = intval($this->module->params[$param]);
        }
        $this->pageSize = $this->pageSizeAdmin; // default pageSize for model

        $this->langHelper = new $this->module->langHelper;
        $param = 'editAllLanguages';
        $editAllLanguages = empty($this->module->params[$param]) ? false : $this->module->params[$param];
        $this->languages = $this->langHelper->activeLanguages($editAllLanguages);
        if (empty($this->langCodeMain) ) {
            $this->langCodeMain = $this->langHelper->normalizeLangCode(Yii::$app->language);
        }

    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'owner_id', 'prio'], 'integer'],
            ['is_visible', 'boolean'],
            ['aftersave', 'safe'],

            ['slug', 'required'],
            ['slug', 'string', 'min' => $this->slugMinLength, 'max' => 255],
            ['slug', 'trim'],
            ['slug', 'filter', 'filter' => function ($value) {
                $value = strtolower($value);
                $value = trim($value, '-');
                return $value;
            }],
            ['slug', 'match', 'pattern' => '/^[a-z0-9\-]+$/',
                'message' => Yii::t($this->tcModule, 'Only small latin letters, digits and hyphen')
            ],
            ['slug', 'unique',
                'targetAttribute' => ['parent_id', 'slug'],
                'message' => Yii::t($this->tcModule, 'Such slug (alias) already exists for this parent')
            ],

            ['route', 'string', 'max' => 255],

          //['route', 'match', 'pattern' => '/^[\/|\[]|(\[\s*[\'\"])/',
            ['route', 'match', 'pattern' => '/^[\/|\[]/',
                'message' => Yii::t($this->tcModule, "Link must begin with slash '/'")
            ]
/*
            ['route', 'match', 'pattern' => '/^[a-z0-9\-\/]+$/',
                'message' => Yii::t($this->tcModule, 'Only small latin letters, digits, hyphen and slash')
            ]
*/
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t($this->tcModule, 'ID'),
            'parent_id' => Yii::t($this->tcModule, 'Parent'),
            'slug' => Yii::t($this->tcModule, 'Alias / URL part'),
            'route' => Yii::t($this->tcModule, 'Link / Route'),
            'is_visible' => Yii::t($this->tcModule, 'Visible'),
            'owner_id' => Yii::t($this->tcModule, 'Author'),
            'create_time' => Yii::t($this->tcModule, 'Create Time'),
            'update_time' => Yii::t($this->tcModule, 'Update Time'),
            'title' => Yii::t($this->tcModule, 'Menu item / Title'),
        ];
    }

    /**
     * @inheritdoc
     * @return ContentQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ContentQuery(get_called_class());
    }

    /**
     * @param integer $modelId main model ID
     * @return string relative subdir without main upload dir prefix
     */
    public static function getImageSubdir($modelId)
    {
        $subdir = floor($modelId / 1000) . '/' . $modelId;
        return $subdir;
    }

    /** Array of i18n-models in format [id][langCode] => i18n-model */
    protected static $_i18n = [];
    /**
     * Declares a `has-many` relation.
     * @return array of i18n-models in format langCode => i18nModel
     */
    public function getI18n()
    {
        $id = $this->id;
        if (!static::$caching || empty(static::$_i18n[$id])) {
            $result = $this->prepareI18nModels();
            if (static::$caching) {
                static::$_i18n[$id] = $result;
            } else {
                return $result;
            }

        }
        return static::$_i18n[$id];
    }
    /** @return ActiveQueryInterface query object, not data */
    public function getJoined()
    {
        return $this->hasMany(
            $this->module->model(static::I18N_JOIN_MODEL)->className(),
            [ static::I18N_JOIN_PRIM_KEY => 'id' ]
        );
    }
    /**
     * Prepare i18n-models array, create new if need.
     * No error if new language add or not found joined record - will create new i18n-model with default values.
     * @return array in format langCode => i18n-model's object
     */
    public function prepareI18nModels()
    {
        $mI18n = $this->getJoined()->all();
        $modelsI18n = [];
        foreach ($mI18n as $modelI18n) {
            $modelI18n->correctSelectedText();
            $modelsI18n[$modelI18n->lang_code] = $modelI18n;
        }
        foreach ($this->languages as $langCode => $lang) {
            if (empty($modelsI18n[$langCode])) {
                $newI18n = $this->module->model(static::I18N_JOIN_MODEL);
                $modelsI18n[$langCode] = $newI18n->loadDefaultValues();
                $modelsI18n[$langCode]->lang_code = $langCode;
            }
        }
        return $modelsI18n;
    }

    /**
     * @inheritdoc
     * @return boolean whether `load()` found the expected form in `$data`.
     */
    public function load($data, $formName = null)
    {
        $result = parent::load($data, $formName);
        if ($result) {
            $i18nFormName = $this->module->model(static::I18N_JOIN_MODEL)->formName();
            foreach ($this->languages as $langCode => $lang) {
                if (!empty($data[$i18nFormName][$langCode])) {
                    $i18nResult = $this->i18n[$langCode]->load($data[$i18nFormName][$langCode], '');
                    if (!$i18nResult) {
                        $result = false;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     * @param array $attributeNames list of attribute names that should be validated.
     * If this parameter is empty, it means any attribute listed in the applicable validation rules should be validated.
     * @param boolean $clearErrors whether to call [[clearErrors()]] before performing validation
     * @return boolean whether the validation is successful without any error.
     * @throws InvalidParamException if the current scenario is unknown.
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        if ($clearErrors) $this->errorLang = null;

        $result = parent::validate($attributeNames, $clearErrors);

        if ($this instanceof ContentSearch) return $result;
        
        if (!$this->isNewRecord) {
            // check if new parent_id not make loop in tree for $this->id
            if (static::existsInParentsChain($this->id, $this->parent_id)) {
                $modelTo = static::findOne($this->parent_id);
                $errmsg = Yii::t($this->tcModule,
                    "Node #{parent} '{toalias}' can't become parent of edited node #{id} '{slug}'"
                    . " because this node #{id} already exists among relatives of #{parent} (will loop in tree)"
                    , [ 'id'      => $this->id,
                        'slug'    => $this->slug,
                        'parent'  => $this->parent_id,
                        'toalias' => $modelTo->slug,
                      ]);
                $this->addError('parent_id', $errmsg);
                $result = false;
            }
        }

        // Joined primary key value among i18n-attributes can't set before create main record.
        // Unset it to avoid validation error. But this attribute will (re)set in $this->save().
        //$attributeNames = $this->module->model(static::I18N_JOIN_MODEL)->activeAttributes();
        //unset($attributeNames[array_search(static::I18N_JOIN_PRIM_KEY, $attributeNames)]);
        //!! simple add to modelI18n::rules(): ['content_id', 'safe'] and delete other content_id-rules

        $eitherValidation = false;
        foreach ($this->languages as $langCode => $lang) {
            if (!empty($this->i18n[$langCode]->title)) $eitherValidation = true;
            if (!empty($this->i18n[$langCode]->text))  $eitherValidation = true;

            //$i18nResult = $this->i18n[$langCode]->validate($attributeNames, $clearErrors);
            $i18nResult = $this->i18n[$langCode]->validate(null, $clearErrors);
            if (!$i18nResult) {
                $result = false;
                $this->errorLang = $langCode;
            }
        }

        if (!$eitherValidation) {
            Yii::$app->session->setFlash('error', Yii::t($this->tcModule, 'At least one title or text field must be fill'));
            $result = false;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->create_time = new Expression('NOW()');
            if (empty($this->owner_id)) $this->owner_id = Yii::$app->user->id;
        }

        if (empty($this->parent_id)) $this->parent_id = 0; // '' => 0
        
        if ($insert || ($this->parent_id != $this->oldAttributes['parent_id'])) {
            $maxPrio = $this->find()->where(['parent_id' => $this->parent_id])->max('prio');
            $this->prio = intval($maxPrio) + 1;
        }

        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     * Empty i18n-model will not save.
     * Existing i18n-model will delete if clean it's data.
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if ($runValidation && !$this->validate($attributeNames)) {
            return false;
        }
        $transaction = static::getDb()->beginTransaction();
        $i18n = $this->i18n; // on create $this->i18n will change after save main model because will change $this->id from null
        try {
            $result = parent::save($runValidation, $attributeNames);
            if ($result) {
                // save multilang models
                foreach ($this->languages as $langCode => $lang) {
                    $modelI18n = $i18n[$langCode];
                    $joinKey = static::I18N_JOIN_PRIM_KEY;
                    $modelI18n->$joinKey = $this->id;

                    if ($modelI18n->hasData()) {
                        $i18nResult = $modelI18n->save();
                        if (!$i18nResult) {
                            $result = false;
                        }
                    } elseif (!empty($modelI18n->id)) { // don't save empty i18n-data
                        $modelI18n->delete();
                    }
                }
            }
        } catch (Exception $e) {
            $transaction->rollBack(); // throw $e;

            $msg = Yii::t($this->tcModule, 'Saving unsuccessfull');
            $msgFull = Yii::t($this->tcModule, 'Saving unsuccessfull by the reason') . ': ' . $e->getMessage();
            Yii::error($msgFull);
            $showError = isset($this->module->params['showAdminSqlErrors']) && $this->module->params['showAdminSqlErrors'];
            Yii::$app->session->setFlash('error', $showError ? $msgFull : $msg);
            return false;
        }
        if ($result) {
            $transaction->commit();
        }
        return $result;
    }

    /**
     * @inheritdoc
     * @return integer|false the number of rows deleted, or `false` if the deletion is unsuccessful for some reason.
     * Note that it is possible that the number of rows deleted is 0, even though the deletion execution is successful.
     * @throws \Exception in case delete failed.
     *
     * Delete also i18n-records from joined table
     */
    public function delete()
    {
        if ($this->hasChildren()) {
            Yii::$app->session->setFlash('error', Yii::t($this->tcModule, "Can't delete node with children"));
            return false;
        }
        
        $id = $this->id;
        $result = false;
        $transaction = static::getDb()->beginTransaction();
        try {
            $modelsI18n = $this->i18n;
            $numRows = 0;
            $result = true;
            foreach ($modelsI18n as $modelI18n) {
                $result = $modelI18n->deleteInternal();
                if ($result === false) break;
                $numRows += $result;
            }
            if ($result !== false) {
                $result = $this->deleteInternal();
            }
            if ($result === false) {
                $transaction->rollBack();
            } else {
                $numRows += $result;
                $result = $numRows;
                $transaction->commit();
                $this->deleteFiles($id);
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            //throw $e;
            Yii::$app->session->setFlash('error'
              , Yii::t($this->tcModule, 'Deletion unsuccessfull by the reason') . ': ' . $e->getMessage());
            $result = false;
        }
        return $result;
    }

    /**
     * Delete uploaded files connected with the model.
     * @param integet $id model id
     * Note that Yii2-advanced temblate has 2 web roots with uploads. Here delete only one.
     * @see In demo application asbsoft/yii2-app_4_170405 uploads-folder is only one and out of any web roots.
     */
    protected function deleteFiles($id)
    {
        $subdir = static::getImageSubdir($id);
        if (!empty($this->module->params['uploadsContentDir'])) {
            $uploadsDir = Yii::getAlias($this->module->params['uploadsContentDir']) . '/' . $subdir;
            //@FileHelper::removeDirectory($uploadsDir);
            rename($uploadsDir, $uploadsDir . '~remove~' . date('ymd~His') . '~');
        }
        if (array_key_exists('@webfilespath', Yii::$aliases) && !empty($this->module->params['filesSubpath'])) {
            $webfilesDir = Yii::getAlias('@webfilespath') . '/' . $this->module->params['filesSubpath'] . '/' . $subdir;
            @FileHelper::removeDirectory($webfilesDir);
        }
    }

    /**
     * Correct text field(s) after select from database.
     */
    public function correctSelectedText()
    {
        $contentHelper = $this->module->contentHelper;
        $this->text = $contentHelper::afterSelectBody($this->text);
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        $this->parent_id = intval($this->parent_id); // '' -> 0
        
        return parent::beforeValidate();
    }

}
