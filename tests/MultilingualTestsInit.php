<?php

namespace DevGroup\Multilingual\tests;

use yii\web\Application;
use Yii;

class MultilingualTestsInit  extends \PHPUnit_Framework_TestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        (new Application([
            'id' => 'unit',
            'basePath' => __DIR__,
            'bootstrap' => ['log', 'multilingual'],
            'controllerNamespace' => 'DevGroup\Multilingual\tests\controllers',
            'components' => [
                'log' => [
                    'traceLevel' => 10,
                    'targets' => [
                        [
                            'class' => 'yii\log\FileTarget',
                            'levels' => ['info'],
                        ],
                    ],
                ],
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ . '/index.php',
                    'scriptUrl' => '/index.php',
                ],
                'cache' => [
                    'class' => '\yii\caching\DummyCache',
                ],
                'urlManager' => [
                    'class' => \DevGroup\Multilingual\components\UrlManager::className(),
                ],
                'multilingual' => [
                    'class' => \DevGroup\Multilingual\Multilingual::className(),
                    'handlers' => [
                        [
                            'class' => \DevGroup\Multilingual\DefaultGeoProvider::className(),
                            'default' => [
                                'country' => [
                                    'name' => 'Russia',
                                    'iso' => 'ru',
                                ],
                            ],
                        ],
                    ],
                ],
                'filedb' => [
                    'class' => 'yii2tech\filedb\Connection',
                    'path' => __DIR__ . '/data',
                ],
            ],
        ]));
    }

    /**
     * @inheritdoc
     */
    public function tearDown()
    {
        if (\Yii::$app && \Yii::$app->has('session', true)) {
            \Yii::$app->session->close();
        }
        \Yii::$app = null;
    }

    /**
     * Resets Yii2 Request component so it can handle another fake request and resolves it
     */
    protected function resolve()
    {
        Yii::$app->set('request', [
            'class' => \yii\web\Request::className(),
            'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
            'scriptFile' => __DIR__ . '/index.php',
            'scriptUrl' => '/index.php',
        ]);
        return Yii::$app->request->resolve();
    }
}