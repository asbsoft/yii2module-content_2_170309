<?php

namespace asb\yii2\modules\content_2_170309\models;

use asb\yii2\common_2_170212\i18n\LangHelper;

use asb\yii2\modules\content_2_170309\Module;
use asb\yii2\modules\content_2_170309\models\ContentI18n;

use Yii;
use yii\db\ActiveQuery;
use yii\base\InvalidConfigException;

/**
 * This is the ActiveQuery class for [[Content]].
 * @see Content
 *
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class ContentQuery extends ActiveQuery
{
    public $tableAliasMain = 'main';
    public $tableAliasI18n = 'i18n';
    
    public $langCodeMain;

    public function init()
    {
        parent::init();

        $this->alias($this->tableAliasMain);

        if (empty($this->langCodeMain) ) {
            $this->langCodeMain = LangHelper::normalizeLangCode(Yii::$app->language);

/*?? error on run by codeception
            $module = Module::getModuleByClassname(Module::className()); //?? return NULL in func tests
            //$module = Module::getModuleByClassname(Module::className(), true); // load anonimous follow translations problem
            if (empty($module)) {//var_dump(array_keys(Yii::$app->modules));var_dump(array_keys(Yii::$app->loadedModules));
                throw new InvalidConfigException("Can't load content module " . Module::className());
            }
            $langHelper = $module->langHelper;
            $this->langCodeMain = $langHelper::normalizeLangCode(Yii::$app->language);
/**/
        }
    }

    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     */
    public function count($q = '*', $db = null)
    {
        $this
            ->alias($this->tableAliasMain)
            ->leftJoin([$this->tableAliasI18n => ContentI18n::tableName()] //!! join here, not in search model
                , "{$this->tableAliasMain}.id = {$this->tableAliasI18n}.content_id "
                  . " AND {$this->tableAliasI18n}.lang_code = '{$this->langCodeMain}'"
              );
        return parent::count($q, $db);
    }

    /**
     * @inheritdoc
     * @return Content[]|array
     */
    public function all($db = null)
    {
        $this
            ->alias($this->tableAliasMain)
            ->leftJoin([$this->tableAliasI18n => ContentI18n::tableName()] //!! join here, not in search model
                , "{$this->tableAliasMain}.id = {$this->tableAliasI18n}.content_id "
                  . " AND {$this->tableAliasI18n}.lang_code = '{$this->langCodeMain}'")
            ->select([
                "{$this->tableAliasMain}.*",
                "{$this->tableAliasI18n}.title AS title",
                "{$this->tableAliasI18n}.text AS text",
            ]);//list ($sql, $parms) = Yii::$app->db->getQueryBuilder()->build($this);var_dump($sql);var_dump($parms);exit;
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Content|array|null
     */
    public function one($db = null)
    {//echo __METHOD__;var_dump($this->langCodeMain);var_dump($this->where);
        if (isset($this->where['id'])) {
            $this->where["{$this->tableAliasMain}.id"] = $this->where['id'];
            unset($this->where['id']);
        }
        $this
            ->alias($this->tableAliasMain)
            ->leftJoin([$this->tableAliasI18n => ContentI18n::tableName()] //!! join here, not in search model
                , "{$this->tableAliasMain}.id = {$this->tableAliasI18n}.content_id "
                  . " AND {$this->tableAliasI18n}.lang_code = '{$this->langCodeMain}'")
            //->where(["{$alias}.id" => $id]) //!! not 'id' by default
            ->where($this->where)
            ->select([
                "{$this->tableAliasMain}.*",
                "{$this->tableAliasI18n}.title AS title",
                "{$this->tableAliasI18n}.text AS text",
            ]);//list ($sql, $parms) = Yii::$app->db->getQueryBuilder()->build($this);var_dump($sql);var_dump($parms);exit;
        return parent::one($db);
    }

    /**
     * @inheritdoc
     * Fix: change table name to table alias for yii\validators\UniqueValidator @Yii2.0.11.2
     * @since Yii2.0.11
     */
    public function andWhere($condition, $params = [])
    {//echo __METHOD__;var_dump($condition);//var_dump($params);
        $mc = $this->modelClass;
        $tableName = $mc::tableName();

        $newCondition = [];
        foreach ($condition as $key => $value) {
            if (is_array($value)) {
                $newValue = [];
                foreach ($value as $k => $v) {
                    $kNew = str_replace($tableName, $this->tableAliasMain, $k); //!!
                    $newValue[$kNew] = $v;
                }
                $newCondition[$key] = $newValue;
            } else {
                $newCondition[$key] = $value;
            }
        }//echo'FIXED:';var_dump($newCondition);
        return parent::andWhere($newCondition, $params);
    }


}
