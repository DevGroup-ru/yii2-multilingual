<?php

namespace DevGroup\Multilingual\controllers;

use DevGroup\AdminUtils\controllers\BaseController;
use Yii;
use DevGroup\Multilingual\models\CountryLanguage;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * CountryLanguageController implements the CRUD actions for CountryLanguage model.
 */
class CountryLanguageController extends BaseController
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
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['edit', 'id' => $model->id]);
        } else {
            return $this->render(
                'edit',
                [
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
