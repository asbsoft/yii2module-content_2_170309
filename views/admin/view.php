<?php

/* @var $this yii\web\View */
/* @var $model asb\yii2\modules\content_2_170309\models\Content */

    use yii\helpers\Html;
    use yii\widgets\DetailView;


    $tc = $this->context->tcModule;

    $this->title = $model->id;
    $this->params['breadcrumbs'][] = ['label' => Yii::t($tc, 'Contents'), 'url' => ['index']];
    $this->params['breadcrumbs'][] = $this->title;

    $model->orderBy = $model::$defaultOrderBy;
    $model->page = $model->calcPage($model::find()->where(['parent_id' => $model->parent_id]));

?>
<div class="content-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t($tc, 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t($tc, 'Delete'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t($tc, 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
        <?= Html::a(Yii::t($tc, 'Return to list'), ['index',
                'parent' => $model->parent_id,
                'page'   => $model->page,
                'id'     => $model->id,
                'sort'   => $model->orderByToSort(),
            ], ['class' => 'btn btn-success']) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'parent_id',
            'slug',
            'is_visible',
            'owner_id',
            'create_time',
            'update_time',
        ],
    ]) ?>

</div>
