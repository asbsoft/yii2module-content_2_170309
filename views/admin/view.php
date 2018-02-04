<?php
    /* @var $this yii\web\View */
    /* @var $model asb\yii2\modules\content_2_170309\models\Content */
    /* @var $modelsI18n array of asb\yii2\modules\content_2_170309\models\ContentI18n */
    /* @var $page integer */
    /* @var $frontendLinks array of string */

    use asb\yii2\modules\content_2_170309\models\Content;
    use asb\yii2\modules\content_2_170309\models\ContentSearch;
    use asb\yii2\modules\content_2_170309\models\ContentMenuBuilder;

    use asb\yii2\common_2_170212\assets\FlagAsset;
    use yii\bootstrap\BootstrapAsset;

    use yii\helpers\Html;
    use yii\helpers\Url;
    use yii\widgets\DetailView;


    $assetsFlag = BootstrapAsset::register($this);
    $assetsFlag = FlagAsset::register($this);
    $assets = $this->context->module->registerAsset('AdminAsset', $this); // $assets = AdminAsset::register($this);

    $moduleUid = $this->context->module->uniqueId;
    $tc = $this->context->tcModule;

    $title = Yii::t($tc, 'Content #{id}', ['id' => $model->id]);
    $this->params['breadcrumbs'][] = ['label' => Yii::t($tc, 'Contents'), 'url' => ['index']];
    $this->params['breadcrumbs'][] = $title;

    $this->title = Yii::t($tc, 'Adminer') . ' - ' . $title;

    $lh = $this->context->module->langHelper;
    $editAllLanguages = empty($this->context->module->params['editAllLanguages'])
                      ? false : $this->context->module->params['editAllLanguages'];
    $languages = $lh::activeLanguages($editAllLanguages);

    $activeTab = $this->context->langCodeMain;
    $actionViewUid = $this->context->module->uniqueId . '/main/view';

    $slugPath = $model::getNodePath($model);

?>
<div class="content-admin-view">

    <h2><?= Html::encode($this->title) ?></h2>
    <h3><?= Html::encode($slugPath) ?></h3>

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
                $moduleInfo = $model::checkModuleLink($model);
                foreach ($languages as $langCode => $lang):
                    $countryCode2 = strtolower(substr($langCode, 3, 2));
                    $flag = '<span class="flag f16"><span class="flag ' . $countryCode2 . '" title="' . $lang->name_orig . '"></span></span>';
                    $labels = $modelsI18n[$langCode]->attributeLabels();
                    $modelI18n = $modelsI18n[$langCode];
            ?>
                <div id="tab-<?= $langCode ?>" class="tab-pane <?php if ($activeTab == $langCode): ?>active<?php endif; ?>">
                    <div class="cintent-comment">
                        <div class="country-flag f32 pull-left"><span class="flag <?= $countryCode2 ?>" title="<?= $lang->name_orig ?>"></span></div>
                        <div class="link-or-text">
                        <?php
                            if (!empty($moduleInfo['text'])) {
                                if (empty($moduleInfo['hrefs'][$langCode])) {
                                    echo $moduleInfo['text'];
                                } else {
                                    // link to module
                                    echo Html::a($moduleInfo['text'], $moduleInfo['hrefs'][$langCode], ['target' => '_blank']);
                                }
                            } else {
                                if (!$lang->is_visible) {
                                    echo Yii::t($tc, 'This language not show at frontend');
                                }
                                if (!$model->is_visible) {
                                    echo Yii::t($tc, 'Content invisible at frontend');
                                } elseif (empty($model->i18n[$langCode]->text) && empty($model->route)) {
                                    echo Yii::t($tc, 'No content to show');
                                } elseif ($model->hasInvisibleParent()) {
                                    if (empty($model->i18n[$langCode]->title)) {
                                        echo Yii::t($tc, 'For use as text block only because has invisible parent node and empty title');
                                    } else {
                                        if (!empty($model->route)) {  // external/internal link
                                            //$link = ContentMenuBuilder::routeToLink($model->route);
                                            //echo Html::a(htmlspecialchars($link), $link, ['target' => '_blank']);                                    
                                              echo Html::a(htmlspecialchars($frontendLinks[$langCode]), $frontendLinks[$langCode], ['target' => '_blank']);
                                        } else {
                                            //$link = Url::toRoute(['main/show', 'id' => $model->id, 'slug' => $model->slug]);
                                            echo Yii::t($tc, 'For use as text block or submenu page because has invisible parent node.')
                                                 . '<br />' . Yii::t($tc, 'Link for submenu') . ': '
                                               //. Html::a($link, $link, ['target' => '_blank']);
                                                 . Html::a($frontendLinks[$langCode], $frontendLinks[$langCode], ['target' => '_blank']);
                                        }
                                    }
                                } else {
                                  //$link = Url::toRoute(['main/view', 'id' => $model->id, 'lang' => $langCode], true);//?? no such route
                                  //echo Html::a($link, $link, ['target' => '_blank']);
                                    echo Html::a($frontendLinks[$langCode], $frontendLinks[$langCode], ['target' => '_blank']);
                                }
                            }
                        ?>
                        </div>
                    </div>
                    <div class="h4 content-label">
                    <?php if ($model->is_visible && !$moduleInfo && !empty($model->i18n[$langCode]->title)): ?>
                            <?= $model->i18n[$langCode]->title ?>
                    <?php else: ?>
                            <?= Yii::t($tc, '(no title)') ?>
                    <?php endif; ?>
                    </div>
                    <?php if ($model->is_visible && !$moduleInfo && !empty($model->i18n[$langCode]->text)): ?>
                        <div class="content-example">
                            <?php $savedTitle = $this->title; ?>
                            <?= Yii::$app->runAction("{$moduleUid}/main/view", [ // show as content page will display at frontend
                                    'id'        => $model->id,
                                    'strict'    => false,
                                    'langCode'  => $langCode,
                                    'useLayout' => false,
                                    'showEmptyContent' => true,
                                ]); ?>
                            <?php $this->title = $savedTitle; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <br style="clear:both" />
    <hr />

</div>
