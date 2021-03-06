<?php

    /* @var $this yii\web\View */
    /* @var $searchModel asb\yii2\modules\content_2_170309\models\ContentSearch */
    /* @var $dataProvider yii\data\ActiveDataProvider */
    /* @var $params array */

    use asb\yii2\modules\content_2_170309\models\Formatter;

    use asb\yii2\common_2_170212\widgets\grid\ButtonedActionColumn;
    use asb\yii2\common_2_170212\widgets\Alert;

    use yii\helpers\Html;
    use yii\helpers\Url;
    use yii\helpers\ArrayHelper;
    use yii\grid\GridView;
    use yii\bootstrap\Modal;


    $assets = $this->context->module->registerAsset('AdminAsset', $this); // inherited

    $gridId = 'list-grid';
    $gridHtmlClass = 'content-list-grid';
    $buttonSearchId = 'btn-search';

    $tc = $this->context->tcModule;

    $formName = $searchModel->formName();

    $currentId = empty($params['id']) ? 0 : $params['id'];
    $parentId = isset($params['parent']) ? $params['parent'] : 0;
    if (empty($parentId) || $parentId == '-') {
        $parentModel = null;
    } else {
        $parentModel = $searchModel::findOne($parentId);
    }

    $paramSort = Yii::$app->request->get('sort', '');
    if ($parentId !== '-' && (empty($paramSort) || 'prio' == $paramSort)) { //!! && empty($params[$formName][...])
        $actionColumnTemplate = '{change-visible} {view} {update} {shift-down} {shift-up}';
    } else {
        $actionColumnTemplate = '{change-visible} {view} {update}';
    }
    if (Yii::$app->user->can('roleContentModerator')) {
        $actionColumnTemplate .= ' {delete}';
    }

    $moduleUid = $this->context->module->uniqueId;
    $showTreeAction = "{$moduleUid}/admin/show-tree";
    $indexRoute = "/{$moduleUid}/admin/index";

    $title = Yii::t($tc, 'Contents');
    $this->title = Yii::t($tc, 'Adminer') . ' - ' . $title;
    $this->params['breadcrumbs'][] = ['label' => $title, 'url' => ['index']];

    $langHelper = $this->context->module->langHelper;
    $langCodeMain = $langHelper::normalizeLangCode(Yii::$app->language);

    $paramSearch = Yii::$app->request->get($formName, []);
    foreach ($paramSearch as $key => $val) {
        if (empty($val)) unset($paramSearch[$key]);
    }
    $pager = $dataProvider->getPagination();
    $this->params['buttonOptions'] = ['data' => ['search' => $paramSearch, 'sort' => $paramSort, 'page' => $pager->page + 1]];

    $userIdentity = $this->context->module->userIdentity;
    $usersNamesList = method_exists($userIdentity, 'usersNames') ? $userIdentity::usersNames() : false;
    $userFilter = (Yii::$app->user->can('roleContentModerator') && $usersNamesList) ? $usersNamesList : false;
    
?>
<div class="content-index">
    <div>
        <div class="col-md-2">
            <h1 style="margin-top: 0"><?= Html::encode($title) ?></h1>
        </div>
        <div class="col-md-9">
            <?= Alert::widget(); ?>
        </div>
        <div class="col-md-1 text-right">
            <?= Html::button('?', [
                   'id' => 'show-instruction',
                   'class' => 'btn',
                   'title' => Yii::t($tc, 'Instruction'),
                ]) ?>
        </div>
    </div>
    <br style="clear:both" />

    <div class="col-md-4 content-tree-container">
        <?= Yii::$app->runAction($showTreeAction, ['active' => $parentId]) ?>
    </div>

    <div class="col-md-8">
        <div class="col-xs-6">
            <h4>
                <?php if ($parentId === '-'): ?>
                    <?= Yii::t($tc, 'All nodes') ?>
                <?php elseif ($parentId == 0): ?>
                    [<?= Yii::t($tc, 'root') ?>]
                <?php else: ?>
                    <?= "#{$parentId}:" ?>
                    <?= '/' . $parentModel::nodePath($parentId) ?>
                    <br />
                    <?= Html::encode($parentModel->i18n[$langCodeMain]->title) ?: Yii::t($tc, '[no title]') ?>
                <?php endif; ?>
            </h4>
        </div>
        <div class="col-xs-3 text-nowrap text-right">
            <?php
                if (!empty($parentModel)) {
                  // change visible
                    $icon = $parentModel->is_visible ? 'ok' : 'minus';
                    $doubt = '';
                    if ($parentModel->is_visible && $parentModel->hasInvisibleParent()) {
                        $doubt = '?';
                    }
                    $options = $this->params['buttonOptions'];
                    $options['title'] = ($parentModel->is_visible ? Yii::t($tc, 'Hide') : Yii::t($tc, 'Show')) . " #{$parentId}";
                    $options['data']['method'] = 'post';
                    $options['data']['confirm'] = Yii::t($tc, 'Are you sure to change visibility of this item?');

                    $url = Url::to(['change-visible', 'id' => $parentId]);
                    echo Html::a("<span class='glyphicon glyphicon-{$icon} btn'><sup>$doubt</sup></span>", $url, $options);

                  // view
                    $options = $this->params['buttonOptions'];
                    $options['title'] = Yii::t($tc, 'View') . " #{$parentId}";
                    $url = Url::to(['view', 'id' => $parentId]);
                    echo Html::a("<span class='glyphicon glyphicon-eye-open btn'></span>", $url, $options);

                  // edit
                    $options = $this->params['buttonOptions'];
                    $options['title'] = Yii::t($tc, 'Edit') . " #{$parentId}";
                    $url = Url::to(['update', 'id' => $parentId]);
                    echo Html::a("<span class='glyphicon glyphicon-pencil btn'></span>", $url, $options);
                
                  // delete
                  if (!$parentModel->hasChildren()) {
                    $options = $this->params['buttonOptions'];
                    $options = ArrayHelper::merge([
                        'title' => Yii::t($tc, 'Delete') . " #{$parentId}",
                        'data' => [
                            'confirm' => Yii::t($tc, 'Are you sure you want to delete this item?'),
                            'method' => 'post',
                        ],
                    ], $this->params['buttonOptions']);
                    $url = Url::to(['delete', 'id' => $parentId]);
                    echo Html::a("<span class='glyphicon glyphicon-trash btn'></span>", $url, $options);
                  }
                }
            ?>
        </div>
        <div class="col-xs-3 text-right media-bottom">
          <?php if(Yii::$app->user->can('roleContentAuthor')): ?>
            <?= Html::a(Yii::t($tc, 'Create Content'), ['create', 'parent' => $parentId], ['class' => 'btn btn-success']) ?>
          <?php elseif(Yii::$app->user->can('roleContentModerator')): ?>
            <span class="small"><?= Yii::t($this->context->tcModule, "Moderator can't create articles") ?></span>
          <?php endif; ?>
        </div>

        <br style="clear:both" />

        <?php if (isset($parentModel)): ?>
            <p class="small"><?= Yii::t($tc, 'Children for node') ?>
        <?php endif; ?>

        <?= GridView::widget([
            'id' => $gridId,
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
          //'filterUrl' => Url::to(['index', 'parent' => '-']), // search in all tree only
            'filterUrl' => Url::to(['index', 'parent' => $parentId]), // search in current subtree
            'options' => [
                'class' => $gridHtmlClass,
            ],
            'formatter' => ['class' => Formatter::className(),
                'timeZone' => 'UTC'
            ],
            'columns' => [
                //['class' => 'yii\grid\SerialColumn'],
                //'parent_id',
                [
                    'attribute' => 'slug',
                    'filter' => Html::activeTextInput($searchModel, 'slug', [
                        'id' => 'search-slug',
                        'class' => 'form-control',
                    ]),
                    'content' => function ($model, $key, $index, $column) use($indexRoute) {
                            $url = Url::toRoute([$indexRoute, 'parent' => $key]);
                            $nodeLink = Html::a($model->slug, $url);
                            $moduleInfo = $model::checkModuleLink($model);
                            $moduleLink = !is_array($moduleInfo) ? '' : (
                                empty($moduleInfo['href'])
                                    ? sprintf("(%s)", $moduleInfo['text']) // without link
                                    : sprintf("<a href=\"%s\">(%s)</a>", $moduleInfo['href'], $moduleInfo['text'])
                            );
                            return sprintf("<span>%s %s</span>", $nodeLink, $moduleLink);
                    },
                ],
                'title',
                [
                    'attribute' => 'is_visible',
                    'label' => Yii::t($tc, 'Visible'),
                    'format' => 'boolean',
                    'filter' => [
                        true  => Yii::t('yii', 'Yes'),
                        false => Yii::t('yii', 'No'),
                    ],
                    'filterInputOptions' => ['class' => 'form-control', 'prompt' => '-' . Yii::t($tc, 'any') . '-'],
                    'options' => [
                        'style' => 'width:85px', //'class' => 'width-min',
                    ],
                ],
                [
                    'attribute' => 'owner_id',
                    'label' => Yii::t($tc, 'Author'),
                    'format' => 'username',
                    'filter' => $userFilter,
                    'filterInputOptions' => ['class' => 'form-control', 'prompt' => '-' . Yii::t($tc, 'all') . '-'],
                ],
                // 'create_time',
                // 'update_time',
                [
                    'attribute' => 'id',
                    'options' => ['class' => 'col-md-1'],
                    //'format' => 'text',
                    'contentOptions' => ['class' => 'align-right'],
                    'filterInputOptions' => [
                        'class' => 'form-control align-center',
                    ],
                ],
                [ 
                    'class' => ButtonedActionColumn::className(),//'class' => 'yii\grid\ActionColumn',
                    'header' => Yii::t($tc, 'Actions'),
                    'buttonSearchId' => $buttonSearchId,
                    'contentOptions' => ['style' => 'white-space: nowrap;'],
                    'template' => $actionColumnTemplate,
                    'visibleButtons' => [
                        'shift-down' => function($model, $key, $index) use($pager) {
                            $rest = $pager->totalCount - $pager->pageSize * $pager->page;
                            return $index < $rest - 1;
                        },
                        'shift-up' => function($model, $key, $index) use($pager) {
                            return $index > 0 || $pager->page > 0;
                        },
                        'delete' => function ($model, $key, $index) {
                            return !$model->hasChildren();
                        },
                    ],
                    'buttons' => [
                        'change-visible' => function($url, $model, $key) use($pager, $formName, $tc) {
                            $icon  = $model->is_visible ? 'ok' : 'minus';
                            $options = $this->params['buttonOptions'];
                            $options['title'] = $model->is_visible ? Yii::t($tc, 'Hide') : Yii::t($tc, 'Show');
                            $options['data']['method'] = 'post';
                            $options['data']['confirm'] = Yii::t($tc, 'Are you sure to change visibility of this item?');

                            $url = Url::to(['change-visible',
                                'id'      => $model->id,
                                'sort'    => $this->params['buttonOptions']['data']['sort'],
                                $formName => $this->params['buttonOptions']['data']['search'],
                                'page'    => $pager->page + 1,
                            ]);
                            $doubt = '';
                            if ($model->is_visible && $model->hasInvisibleParent()) {
                                $doubt = '?';
                            }
                            return Html::a("<span class='glyphicon glyphicon-{$icon}'><sup>$doubt</sup></span>", $url, $options);
                        },
                        'shift-down' => function($url, $model, $key) use($tc, $pager) {
                            $options = $this->params['buttonOptions'];
                            $options['title'] = Yii::t($tc, 'Shift down');
                            $options['data']['method'] = 'post';
                            $url = Url::to(['shift',
                                'direction' => 'down',
                                'id'      => $model->id,
                                'sort'    => $this->params['buttonOptions']['data']['sort'],
                                'page'    => $pager->page + 1,
                            ]);
                            return Html::a("<span class='glyphicon glyphicon-arrow-down'></span>", $url, $options);
                        },
                        'shift-up' => function($url, $model, $key) use($tc, $pager) {
                            $options = $this->params['buttonOptions'];
                            $options['title'] = Yii::t($tc, 'Shift up');
                            $options['data']['method'] = 'post';
                            $url = Url::to(['shift',
                                'direction' => 'up',
                                'id'      => $model->id,
                                'sort'    => $this->params['buttonOptions']['data']['sort'],
                                'page'    => $pager->page + 1,
                            ]);
                            return Html::a("<span class='glyphicon glyphicon-arrow-up'></span>", $url, $options);
                        },
                    ],
                ],
            ],
        ]); ?>

    </div>
</div>

<?php
    Yii::$app->i18n->translations[$tc]->forceTranslation = true;
    Modal::begin([
        'id' => 'instruction-window',
        'header' => '<h2 class="text-center">' . Yii::t($tc, 'Instruction') . '</h2>',
    ]);
        echo Yii::t($tc, 'INSTRUCTION_TEXT');
    Modal::end();
?>

<?php
    $this->registerJs("
        jQuery('.{$gridHtmlClass} table tr').each(function(index) {
            var elem = jQuery(this);
            var id = elem.attr('data-key');
            if (id == '{$currentId}') {
               elem.addClass('bg-success'); //?? overwrite by .table-striped > tbody > tr:nth-of-type(2n+1)
               elem.css({'background-color': '#DFD'}); // work always
            }
        });

        jQuery('#show-instruction').bind('click', function() {
            jQuery('#instruction-window').modal('show');
        });
    ");
?>
