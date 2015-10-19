<?php

namespace DevGroup\Multilingual\tests\controllers;

use Yii;
use yii\web\Controller;

class SiteController extends Controller
{
    public function actionIndex()
    {
        return 'hello from index';
    }

    public function actionAbout()
    {
        return 'about:)';
    }
}
