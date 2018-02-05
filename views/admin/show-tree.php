<?php

/* @var $this yii\web\View */
/* @var $model asb\yii2\modules\content_2_170309\models\Content */
/* @var integer $active */

    use yii\helpers\Html;
    use yii\helpers\Url;


  //$markoutClass = 'bg-info';
    $markoutClass = 'bg-success';

    $tc = $this->context->tcModule;

    $linkOptions = [
        'title' => Yii::t($tc, 'Work with this node'),
    ];
    $linkOptionsMarked = [
        'class' => 'alert-link text-primary',
    ];

    $optionsAll = $active === '-' ? $linkOptionsMarked : $linkOptions;
    $optionsRoot = $active == '0' ? $linkOptionsMarked : $linkOptions;

    //$list = $model::nodesTreeList($parentId = 0, $level = 0, $shiftPrefix = '.&nbsp;&nbsp;');
    $list = $model::nodesTree($parentId = 0, $level = 0, $shiftPrefix = '.&nbsp;&nbsp;');

?>
<div class="content-tree">
    <?php if (empty($list)): ?>
        <div class="content-tree-empty"><?= Yii::t($tc, 'Tree is empty') ?></div>
    <?php else: ?>
        <div class="content-tree-link <?= $active === '-' ? $markoutClass : '' ?>">
            <?= Html::a(Yii::t($tc, 'All nodes'), Url::to(['index', 'parent' => '-']), $optionsAll); ?>
        </div>
        <div class="content-tree-link <?= $active == '0' ? $markoutClass : '' ?>">
            <?= Html::a(Yii::t($tc, 'root'), Url::to(['index', 'parent' => 0]), $optionsRoot); ?>
        </div>
    <?php endif; ?>

    <?php foreach($list as $id => $label):
             if ($active == $id) $options = $linkOptionsMarked; else $options = $linkOptions;
    ?>
        <div class="content-tree-link <?= $active == $id ? $markoutClass : '' ?>">
            <?= Html::a($label, Url::to(['index', 'parent' => $id]), $options); ?>
        </div>
    <?php endforeach; ?>
</div>
