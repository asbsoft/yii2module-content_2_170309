<?php
/* @var $this yii\web\View */
/* @var $model asb\yii2\modules\content_2_170309\models\Content */
/* @var $modelsI18n array of asb\yii2\modules\content_2_170309\models\ContentI18n */
/* @var $page integer */

    use asb\yii2\modules\content_2_170309\models\Content;
    use asb\yii2\modules\content_2_170309\models\ContentSearch;

    //use asb\yii2\common_2_170212\assets\CommonAsset;
    use asb\yii2\common_2_170212\assets\FlagAsset;

    use yii\helpers\Html;
    use yii\helpers\Url;
    use yii\widgets\DetailView;


    $moduleUid = $this->context->module->uniqueId;
    $tc = $this->context->tcModule;

    $this->title = Yii::t($tc, 'Content #{id}', ['id' => $model->id]);
    $this->params['breadcrumbs'][] = ['label' => Yii::t($tc, 'Contents'), 'url' => ['index']];
    $this->params['breadcrumbs'][] = $this->title;

    //$assetsSys = CommonAsset::register($this);
    $assetsFlag = FlagAsset::register($this);
    //$assets = AdminAsset::register($this);
    $assets = $this->context->module->registerAsset('AdminAsset', $this);//var_dump($assets);

    $lh = $this->context->module->langHelper;
    $editAllLanguages = empty($this->context->module->params['editAllLanguages'])
                      ? false : $this->context->module->params['editAllLanguages'];
    $languages = $lh::activeLanguages($editAllLanguages);//var_dump(array_keys($languages));

    $activeTab = $this->context->langCodeMain;
    $actionViewUid = $this->context->module->uniqueId . '/main/view';//var_dump($actionViewUid);

?>
<div class="content-admin-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('yii', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('yii', 'Delete'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
        <?= Html::a(Yii::t($tc, 'Return to list'), ['index'
              , 'page' => $model->page, 'id' => $model->id
            ], ['class' => 'btn btn-success']) ?>
    </p>
    
    <div class="tabbable content-lang-switch">
        <ul class="nav nav-tabs">
            <?php // multi-lang part - tabs
                foreach ($languages as $langCode => $lang):
                    $countryCode2 = strtolower(substr($langCode, 3, 2));
            ?>
                <li class="<?php if ($activeTab == $langCode): ?>active<?php endif; ?>">
                    <div class="tab-field">
                        <div class="tab-link flag f16">
                            <a href="#tab-<?= $langCode ?>" data-toggle="tab"><?= $lang->name_orig ?></a>
                            <span class="flag <?= $countryCode2 ?>" title="<?= "{$lang->name_orig}" ?>"></span>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="tab-content">
            <?php // multi-lang part - content
              foreach ($languages as $langCode => $lang):
                  $countryCode2 = strtolower(substr($langCode, 3, 2));
                  $flag = '<span class="flag f16"><span class="flag ' . $countryCode2 . '" title="' . $lang->name_orig . '"></span></span>';
                  $labels = $modelsI18n[$langCode]->attributeLabels();
                  //var_dump($modelsI18n[$langCode]->attributes);
                  $modelI18n = $modelsI18n[$langCode];
            ?>
            <div id="tab-<?= $langCode ?>"
                class="tab-pane <?php if ($activeTab == $langCode): ?>active<?php endif; ?>"
            >
                <p>
                    <?php
                        $link = Url::toRoute(['main/view', 'id' => $model->id, 'lang' => $langCode], true);//var_dump($link);
                        //if ($model->is_visible && ContentSearch::canShow($model, $modelI18n)) { //todo
                        if ($model->is_visible && $lang->is_visible) {
                            echo Html::a($link, $link, ['target' => '_blank']);
                        } else {
                            echo Yii::t($tc, 'Content invisible at frontend');
                        }
                    ?>
                </p>
                <div class="content-example">
                    <?php // show as it will show at frontend
                        echo Yii::$app->runAction("{$moduleUid}/main/view", [
                            'id'       => $model->id,
                            'strict'   => false,
                            'langCode' => $langCode,
                            'layout'   => false,
                            'showEmptyContent' => true,
                        ]);
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <br style="clear:both" />
    <hr />

</div>
