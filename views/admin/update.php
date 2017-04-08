<?php

/* @var $this yii\web\View */
/* @var $model asb\yii2\modules\content_2_170309\models\Content */

    use yii\helpers\Html;


    $tc = $this->context->tcModule;

    $this->title = Yii::t($tc, 'Update Content') . ' #' . $model->id;
    $this->params['breadcrumbs'][] = ['label' => Yii::t($tc, 'Contents'), 'url' => ['index']];
    $this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
    $this->params['breadcrumbs'][] = Yii::t($tc, 'Update');

?>
<div class="content-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
