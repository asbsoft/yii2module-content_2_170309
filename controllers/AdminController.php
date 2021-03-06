<?php

namespace asb\yii2\modules\content_2_170309\controllers;

use asb\yii2\modules\content_2_170309\models\Content;
use asb\yii2\modules\content_2_170309\models\ContentI18n;
use asb\yii2\modules\content_2_170309\models\ContentSearch;
use asb\yii2\modules\content_2_170309\models\ContentMenuBuilder;

use asb\yii2\common_2_170212\controllers\BaseAdminMulangController;
use asb\yii2\common_2_170212\base\UniApplication;
use asb\yii2\common_2_170212\base\UniModule;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * AdminController implements the CRUD actions for Content model.
 * @author Alexandr Belogolovsky <ab2014box@gmail.com>
 */
class AdminController extends BaseAdminMulangController
{
    public $pageSizeAdmin = 20; // default
    public $canAuthorEditOwnVisibleArticle = false; // default

    /** Model for getting frontend application */
    public static $sysAppModel = 'ApplicationModel';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        
        $param = 'pageSizeAdmin';
        if (!empty($this->module->params[$param]) && intval($this->module->params[$param]) > 0) {
            $this->$param = intval($this->module->params[$param]);
        }
        $param = 'canAuthorEditOwnVisibleArticle';
        if (isset($this->module->params[$param])) {
            $this->$param = $this->module->params[$param];
        }
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $rules = [
            ['allow' => true, 'actions' => ['index', 'view'], 'roles' => ['roleContentAuthor', 'roleContentModerator']],
            ['allow' => true, 'actions' => ['change-visible', 'shift'], 'roles' => ['roleContentModerator']],
            ['allow' => true, 'actions' => ['create'], 'roles' => ['createContent']],
            ['allow' => true, 'actions' => ['delete'], 'roles' => ['deleteContent']],
            ['allow' => true, 'actions' => ['update', 'show-tree', 'check-route']
                , 'roles' => ['roleContentModerator', 'roleContentAuthor']
                  // + (for actionUpdate) check Yii::$app->user->can('...', [...]) in actionUpdate()
            ],
        ];
        $behaviors = ArrayHelper::merge(parent::behaviors(), [
            //'access' => ['rules' => $rules], // don't merge - will rewrite
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'change-visible' => ['POST'],
                    'shift' => ['POST'],
                    'delete' => ['POST'],
                    'check-route' => ['POST'],
                ],
            ],
        ]);
        
        $behaviors['access']['rules'] = $rules; // rewrite access rules, do not merge with parent
        return $behaviors;
    }

    /**
     * Lists all Content models.
     * @param mixed $parent parent node id, '0' means show root children, '-' means show all nodes.
     * @param integer $page
     * @return mixed
     */
    public function actionIndex($parent = null, $page = 1)
    {
        $searchModel = $this->module->model('ContentSearch');

        // list filter parameters correction
        $params = Yii::$app->request->queryParams;
        if ($parent === '-') { // to show all nodes
            $params['parent'] = $parent;
            unset($params[$searchModel->formName()]['parent_id']);
        } else {
            $params[$searchModel->formName()]['parent_id'] = $params['parent'] = intval($parent);
        }

        if (!Yii::$app->user->can('roleContentModerator') && Yii::$app->user->can('roleContentAuthor')) {
            $params[$searchModel->formName()]['owner_id'] = Yii::$app->user->id;
        }

        $dataProvider = $searchModel->search($params);

        $pager = $dataProvider->getPagination();
        $pager->pageSize = $this->pageSizeAdmin;
        $pager->totalCount = $dataProvider->getTotalCount();

        // page number correction:
        $maxPage = ceil($pager->totalCount / $pager->pageSize);
        if ($page > $maxPage) {
            $pager->page = $maxPage - 1;
        } else {
            $pager->page = $page - 1; //! from 0
        }

        return $this->render('index', compact('dataProvider', 'searchModel', 'params'));
    }

    /**
     * Displays a single Content model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $modelsI18n = $model->prepareI18nModels();

        if ($model->pageSize > 0) {
            $model->orderBy = $model::$defaultOrderBy;
            $model->page = $model->calcPage();
        }

        // build frontend links
        $lh = $this->module->langHelper;
        $editAllLanguages = empty($this->module->params['editAllLanguages'])
                          ? false : $this->module->params['editAllLanguages'];
        $languages = $lh::activeLanguages($editAllLanguages);
        if (Yii::$app instanceof UniApplication && Yii::$app->type == UniApplication::APP_TYPE_BACKEND) {
            $containerModule = $this->module->module;
            if ($containerModule instanceof UniModule && array_key_exists(static::$sysAppModel, $containerModule->models)) {
                $appModel = $containerModule::model(static::$sysAppModel);
                $appFront = $appModel::initFrontendApplication();  // for backend application init separate frontend-application
              //$appFront->urlManager->baseUrl = 'http://www.mysite.com';  // frontend domain - set from app-params
            }
        }
        $moduleUid = $this->module->uniqueId;
        $frontendLinks = [];
        foreach ($languages as $langCode => $lang) {
            $frontendLinks[$langCode] = '';
            if ($model->hasInvisibleParent()) {
                if (empty($model->route)) {
                    $frontendLinks[$langCode] = Url::toRoute(["/{$moduleUid}/main/show", 'id' => $model->id, 'slug' => $model->slug]);
                } else {  // external/internal link
                    $frontendLinks[$langCode] = ContentMenuBuilder::routeToLink($model->route);
                }
            } else {
                $frontendLinks[$langCode] = Url::toRoute(["/{$moduleUid}/main/view"
                  , 'id' => $model->id, 'lang' => $langCode], true);
            }
        }
        if (!empty($appModel)) $appModel::restoreApplication();

        return $this->render('view', compact('model', 'modelsI18n', 'frontendLinks'));
    }

    /**
     * Creates a new Content model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($parent = 0)
    {
        $model = $this->module->model('Content');
        if (!isset($model->parent_id)) {
            $model->parent_id = $parent;
        }
        $post = Yii::$app->request->post();
        $loaded = $model->load($post);

        if ($loaded && $model->save()) {
            if ($model->aftersave != $model::AFTERSAVE_LIST) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                $model->orderBy = $model::$defaultOrderBy;
                $model->page = $model->calcPage($model::find()->where(['parent_id' => $model->parent_id]));
                return $this->redirect(['index',
                    'parent' => $model->parent_id,
                    'page'   => $model->page,
                    'id'     => $model->id,
                    'sort'   => $model->orderByToSort(),
                ]);
            }
        } else {
            return $this->render('create', compact('model'));
        }
    }

    /**
     * Updates an existing Content model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        // check permissions
        if (!Yii::$app->user->can('roleContentModerator') // user is not moderator
         &&  Yii::$app->user->can('roleContentAuthor')    // but is author ...
         && !Yii::$app->user->can('updateOwnContent', [   // ... can edit only own article
                'content' => $model,                      //     - if article unvisible - always can
                'canEditVisible'                          //     - but if not visible - see this param
                    => $this->canAuthorEditOwnVisibleArticle,
            ])
        ) {
            if ($this->canAuthorEditOwnVisibleArticle) {
                throw new ForbiddenHttpException(Yii::t($this->tcModule, 'You can update only your own article'));
            } else {
                throw new ForbiddenHttpException(Yii::t($this->tcModule, 'You can update only your own article still unvisible'));
            }
        }

        $post = Yii::$app->request->post();
        $loaded = $model->load($post);

        $attributes = $model->attributes;
        if (!$this->canAuthorEditOwnVisibleArticle) unset($attributes['is_visible']);
        $attributeNames = array_keys($attributes);

        if ($loaded && $model->save(true, $attributeNames)) {
            if ($model->aftersave != $model::AFTERSAVE_LIST) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                $model->orderBy = $model::$defaultOrderBy;
                $model->page = $model->calcPage($model::find()->where(['parent_id' => $model->parent_id]));
                return $this->redirect(['index',
                    'parent' => $model->parent_id,
                    'page'   => $model->page,
                    'id'     => $model->id,
                    'sort'   => $model->orderByToSort(),
                ]);
            }
        } else {
            return $this->render('update', compact('model'));
        }
    }

    /**
     * Deletes an existing Content model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        $model->orderBy = $model::$defaultOrderBy;
        $model->page = $model->calcPage($model::find()->where(['parent_id' => $model->parent_id]));
        $returnTo = ['index', // try to return to same place - deletion may be unsuccessfull
            'parent_id' => $model->parent_id,
            'page'      => $model->page,
            'id'        => $id,
            'sort'      => $model->orderByToSort(),
        ];

        $model->delete();

        return $this->redirect($returnTo);
    }

    /**
     * Change is_visible attribute for item with $id
     * @param integer $id
     * @param integer $page
     */
    public function actionChangeVisible($id, $page = 1)
    {
        $model = $this->findModel($id);
        $model->is_visible = $model->is_visible ? false: true;
        $model->orderBy = $model::$defaultOrderBy;
        $model->save(false, ['is_visible']);

        $model->page = $model->calcPage($model::find()->where(['parent_id' => $model->parent_id]));

        return $this->redirect(['index',
            'parent' => $model->parent_id,
            'page'   => $model->page,
            'id'     => $id,
            'sort'   => $model->orderByToSort(),
        ]);
    }

    public function actionShowTree($active = 0)
    {
        $model = $this->module->model('Content');
        return $this->renderPartial('show-tree', compact('model', 'active'));
    }

    public function actionShift($direction, $id)
    {
        $params = Yii::$app->request->queryParams;
        $page   = empty($params['page']) ? 1 : $params['page'];
        $sort   = empty($params['sort']) ? 'prio' : $params['sort'];
        //$parent = empty($params['parent']) ? 0 : $params['parent'];

        $errMsg = '';
        $model = $this->findModel($id);
        if (empty($model)) {
            $errMsg = Yii::t($this->tcModule, 'Node #{id} not found', ['id' => $id]);
        } else {
            $model->orderBy = $model::$defaultOrderBy;

            $swapId = $model->getNearId($id, $direction, ['parent_id' => $model->parent_id]);
            if (empty($swapId)) {
                $errMsg = Yii::t($this->tcModule, "Can't find swap ({dir}) for #{id}", [
                    'id' => $id,
                    'dir' => Yii::t($this->tcModule, $direction),
                ]);
            } else {
                if (!$model->swapPrio($id, $swapId)) {
                    $errMsg = Yii::t($this->tcModule, "Can't swap #{id} with #{swapId}", ['id' => $id, 'swapId' => $swapId]);
                }
            }

            $page = $model->calcPage($model::find()->where(['parent_id' => $model->parent_id]));
            $sort = $model->orderByToSort();
            $parent = $model->parent_id;
        }

        if (!empty($errMsg)) Yii::$app->session->setFlash('error', $errMsg);

        return $this->redirect(['index',
            'parent' => $parent,
            'page'   => $page,
            'id'     => $id,
            'sort'   => $sort,
        ]);
    }
    
    /**
     * Finds the Content model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Content the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $model = $this->module->model('Content')->findOne($id);
        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Resolve route-string from $post['route']
     */
    public function actionCheckRoute()
    {
        $post = Yii::$app->request->post();
        if (empty($post['route'])) {
            $result = Yii::t($this->tcModule, 'Error in POST-ed data');
        } else {
            $ctrlLinkPrefix = empty($post['ctrlLinkPrefix']) ? null : $post['ctrlLinkPrefix'];

            $url = ContentMenuBuilder::routeToLink($post['route'], $ctrlLinkPrefix);

            if (Yii::$app instanceof UniApplication && Yii::$app->type == UniApplication::APP_TYPE_BACKEND) {
                // backend application don't have frontend-routes, $url is wrong
                $url = false;

                // if module-container (system-module) has ApplicationModel - it can get frontend application with correct routes
                $containerModule = $this->module->module;
                if ($containerModule instanceof UniModule && array_key_exists(static::$sysAppModel, $containerModule->models)) {
                    $appModel = $containerModule::model(static::$sysAppModel);
                    $appFront = $appModel::initFrontendApplication();
                    $url = ContentMenuBuilder::routeToLink($post['route'], $ctrlLinkPrefix);
                    $appModel::restoreApplication();
                }
            }

            if ($url) {
                $result = htmlspecialchars($url);
            } else {
                $result = Yii::t($this->tcModule, "Can't resolve route '{route}'", ['route' => $post['route']]);
                if (!empty(ContentMenuBuilder::$errorRouteConvert)) {
                    $result .= ', ERROR: ' . ContentMenuBuilder::$errorRouteConvert;
                }
            }
        }
        return $result;
    }

}
