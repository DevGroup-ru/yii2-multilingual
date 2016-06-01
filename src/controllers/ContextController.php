<?php

namespace DevGroup\Multilingual\controllers;

use DevGroup\AdminUtils\controllers\BaseController;
use DevGroup\Multilingual\models\Language;
use Yii;
use DevGroup\Multilingual\models\Context;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ContextController implements the CRUD actions for Context model.
 */
class ContextController extends BaseController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
//            'verbs' => [
//                'class' => VerbFilter::className(),
//                'actions' => [
//                    'delete' => ['POST'],
//                ],
//            ],
        ];
    }

    /**
     * Lists all Context models.
     * @return mixed
     */
    public function actionIndex()
    {
        $model = new Context(['scenario' => 'search']);
        $dataProvider = $model->search(Yii::$app->request->queryParams);
        return $this->render(
            'index',
            [
                'dataProvider' => $dataProvider,
                'model' => $model,
            ]
        );
    }

    /**
     * Updates an existing Context model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionEdit($id = null)
    {
        if ($id === null) {
            $model = new Context;
            $dataProvider = null;
        } else {
            $model = $this->findModel($id);
            $dataProvider = new ActiveDataProvider(
                [
                    'query' => Language::find()->where(['context_id' => $model->id]),
                ]
            );
        }
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['edit', 'id' => $model->id]);
        } else {
            return $this->render(
                'edit',
                [
                    'dataProvider' => $dataProvider,
                    'model' => $model,
                ]
            );
        }
    }

    /**
     * Updates an existing Language model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionEditLanguage($id = null, $contextId = null)
    {
        if ($id === null) {
            $model = new Language();
        } else {
            $model = $this->findLanguageModel($id);
        }
        if ($contextId !== null) {
            $model->context_id = $contextId;
        }
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['edit-language', 'id' => $model->id]);
        } else {
            return $this->render(
                'edit-language',
                [
                    'model' => $model,
                ]
            );
        }
    }

    /**
     * Deletes an existing Context model.
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
     * Deletes an existing Language model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDeleteLanguage($id)
    {
        $model = $this->findLanguageModel($id);
        $model->delete();
        return $this->redirect(['edit', 'id' => $model->context_id]);
    }

    /**
     * Finds the Context model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Context the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Context::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }


    /**
     * Finds the Language model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Language the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findLanguageModel($id)
    {
        if (($model = Language::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
