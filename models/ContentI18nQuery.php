<?php

namespace asb\yii2\modules\content_2_170309\models;

/**
 * This is the ActiveQuery class for [[ContentI18n]].
 *
 * @see ContentI18n
 */
class ContentI18nQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return ContentI18n[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return ContentI18n|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
