<?php

/* @var $this yii\web\View */
/* @var $model asb\yii2\modules\content_2_170309\models\Content */
/* @var $form yii\widgets\ActiveForm */
/* //@var $activeTab string selected language - for switch tabpane on error */

    use asb\yii2\modules\content_2_170309\Module;

    use asb\yii2\common_2_170212\assets\FlagAsset;
    use asb\yii2\common_2_170212\widgets\ckeditor\CkEditorWidget;

    use yii\helpers\Html;
    use yii\widgets\ActiveForm;


    $assetsFlag = FlagAsset::register($this);
    $assets = $this->context->module->registerAsset('AdminAsset', $this); // inherited

    // defaults
    if (empty($heightEditor)) $heightEditor = 240; //px

    if (empty($activeTab)) {
        if (!empty($model->errorLang)) {
            $activeTab = $model->errorLang; // select tab pane with error
        } else {
            $activeTab = $this->context->langCodeMain; // default labg tab pane
        }
    }

    if (empty($model->owner_id)) $model->owner_id = Yii::$app->user->id;
   
    $tc = $this->context->tcModule;

    $userIdentity = $this->context->module->userIdentity;

    $enableEditVisibility = (!Yii::$app->user->can('roleContentModerator') && Yii::$app->user->can('roleContentAuthor')) ? false : true;//var_dump($enableEditVisibility);

    $langHelper = $this->context->module->langHelper;
    $languages = $langHelper::activeLanguages();

    $modelsI18n = $model->i18n;

    $editorOptions = [
        'height' => $heightEditor,
        'language' => substr(Yii::$app->language, 0, 2),
        'filter' => 'image',
      
        'preset' => 'full',     // full editor
      //'preset' => 'standard', // middle
      //'preset' => 'basic',    // minimal editor
    ];

    if (empty($model->id)) { // article not create yet - can't load images
        $managerOptions = false;
    } else {
        $elfController = [$this->context->module->uniqueId . '/el-finder', 'id' => $model->id];
        $managerOptions = [
            'controller' => $elfController,
            'rootPath' => $this->context->module->params['uploadsContentDir'] . '/' . $model::getImageSubdir($model->id),
            'filter' => 'image',
        ];
    }//var_dump($managerOptions);exit;

?>
<div class="content-form">

    <?php $form = ActiveForm::begin([
              'id' => 'form-admin',
              'enableClientValidation' => false, // disable JS-validation
          ]); ?>

        <div class="col-md-5">
            <?= $form->field($model, 'parent_id')->dropDownList($model::nodesTreeList(), [
                    'id' => 'parent-id',
                    'prompt' => '-' . Yii::t($tc, 'root') . '-',
                    'class' => 'form-control',
                    'title' => Yii::t($tc, 'Change parent'),
                ]) ?>
        </div>

        <div class="col-md-5">
            <?= $form->field($model, 'slug')->textInput(['maxlength' => true]) ?>
        </div>

        <br style="clear:both" />

        <div class="col-md-2 text-nowrap">
          <?php if ($enableEditVisibility): ?>
            <?= $form->field($model, 'is_visible')->checkbox() ?>
          <?php else: ?>
            &nbsp;
          <?php endif; ?>
        </div>

        <?php if (Yii::$app->user->can('roleContentModerator')): ?>
        <div class="col-md-5">
            <?= $form->field($model, 'owner_id')->dropDownList($userIdentity::usersNames(), [
                    'id' => 'owner-id',
                    'prompt' => '-' . Yii::t($tc, 'select') . '-',
                    'class' => 'form-control',
                    'title' => Yii::t($tc, 'Change author'),
                ]) ?>
        </div>
        <?php endif; ?>

        <br style="clear:both" />

        <div class="tabbable content-multilang">
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
                ?>
                <div id="tab-<?= $langCode ?>"
                    class="tab-pane <?php if ($activeTab == $langCode): ?>active<?php endif; ?>"
                >
                    <?= $form->field($modelsI18n[$langCode], "[{$langCode}]title",[
                            'options' => [
                                'class'=>'content-title',
                            ],
                        ])->label($flag . ' ' . $labels['title'])
                          ->textInput() ?>

                    <?= $form->field($modelsI18n[$langCode], "[{$langCode}]text")
                        ->label(false)
                      //->label($flag . ' ' . $labels['body'])
                      //->textarea(['rows' => $rowsCountTextarea]) // for debug
                        ->widget(CkEditorWidget::className(), [
                            'id' => "editor-{$langCode}",
                            //'inputOptions' => ['value' => $modelsI18n[$langCode]->body],
                            'editorOptions' => $editorOptions,
                            'managerOptions' => $managerOptions,
                        ])
                    ?>
                
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($model->isNewRecord)): ?>
            <div class="bg-warning"><small><?= Yii::t($this->context->tcModule,
               "When create new record you can't upload images in text editor. You can do this in update mode"
            ) ?></small></div>
            <br />
        <?php endif; ?>

        <br style="clear:both" />

        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t($tc, 'Create') : Yii::t($tc, 'Save'), [
                    'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary'
                ]) ?>
            <?= Html::submitButton(Yii::t($tc, 'Save no view'), [
                   'id' => 'save-no-view',
                   'class' => 'btn btn-success',
                ]) ?>
            <?= $form->field($model, 'aftersave', [
                    'inputOptions' => ['id' => 'aftersave'],
                ])->hiddenInput()->label(false) ?>
        </div>

    <?php ActiveForm::end(); ?>

</div>

<?php
    $aftersave_list = $model::AFTERSAVE_LIST;
    $this->registerJs("
        jQuery('#save-no-view').bind('click', function() {
            jQuery('#aftersave').val('{$aftersave_list}');
        });
    ");
?>