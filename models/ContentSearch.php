<?php

namespace asb\yii2\modules\content_2_170309\models;

use asb\yii2\modules\content_2_170309\models\Content;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Represents the model behind the search form.
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class ContentSearch extends Content
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'parent_id', 'owner_id', 'is_visible'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['slug', 'title', 'text'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $contentClassname = $this->module->model('Content')->className();
        $query = $this->module->model('ContentQuery', [$contentClassname]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'main.id' => $this->id, // ambiguous: 'id' => $this->id,
            'owner_id' => $this->owner_id,
            'parent_id' => $params['parent'] === '-' ? null : $this->parent_id,
            'is_visible' => $this->is_visible,
            //'create_time' => $this->create_time,
            //'update_time' => $this->update_time,
        ]);

        $langHelper = $this->module->langHelper;
        $langCodeMain = $langHelper::normalizeLangCode(Yii::$app->language);

        $query->andFilterWhere(['like', 'slug', $this->slug]);
        $query->andFilterWhere(['like', 'title', $this->title]);
      //$query->andFilterWhere(['like', 'text', $this->text]);

        if (empty($params['sort'])) {
            $query->orderBy($this::$defaultOrderBy);
        } elseif ($params['parent'] === '-') {
            $query->orderBy(['slug' => SORT_ASC]);
        }
            
        //list($sql, $sqlParams) = Yii::$app->db->getQueryBuilder()->build($query);var_dump($sql);var_dump($sqlParams);//exit;
        return $dataProvider;
    }

    /**
     * @inheritdoc
     */
    public function formName()
    {
      //return parent::formName(); // return 'ContentSearch'
        return 'q'; // to optimize GET-query
    }

}
