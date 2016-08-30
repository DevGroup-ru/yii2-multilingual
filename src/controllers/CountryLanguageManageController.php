<?php

namespace DevGroup\Multilingual\controllers;

use DevGroup\AdminUtils\controllers\BaseController;
use DevGroup\Multilingual\models\CountryLanguage;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * CountryLanguageController implements the CRUD actions for CountryLanguage model.
 */
class CountryLanguageManageController extends BaseController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'accessControl' => [
                'class' => '\yii\filters\AccessControl',
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'edit'],
                        'roles' => ['multilingual-view-country-language'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => ['multilingual-delete-country-language'],
                    ],
                    [
                        'allow' => false,
                        'roles' => ['*'],
                    ]
                ],
            ],
        ];
    }

    /**
     * Lists all CountryLanguage models.
     * @return mixed
     */
    public function actionIndex()
    {
        $model = new CountryLanguage(['scenario' => 'search']);
        $dataProvider = $model->search(Yii::$app->request->queryParams);
        return $this->render(
            'index',
            [
                'model' => $model,
                'dataProvider' => $dataProvider,
            ]
        );
    }

    /**
     * Updates an existing CountryLanguage model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionEdit($id = null)
    {
        if ($id === null) {
            $model = new CountryLanguage;
        } else {
            $model = $this->findModel($id);
        }
        $isLoaded = $model->load(Yii::$app->request->post());
        $hasAccess = ($model->isNewRecord && Yii::$app->user->can('multilingual-create-country-language'))
            || (!$model->isNewRecord && Yii::$app->user->can('multilingual-edit-country-language'));
        if ($isLoaded && !$hasAccess) {
            throw new ForbiddenHttpException;
        }
        if ($isLoaded && $model->save()) {
            return $this->redirect(['edit', 'id' => $model->id]);
        } else {
            return $this->render(
                'edit',
                [
                    'hasAccess' => $hasAccess,
                    'model' => $model,
                ]
            );
        }
    }

    /**
     * Deletes an existing CountryLanguage model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }

    /**
     * Finds the CountryLanguage model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CountryLanguage the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CountryLanguage::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
