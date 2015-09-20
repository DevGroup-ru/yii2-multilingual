<?php
/**
 * This unit tests are based on work of Alexander Kochetov (@creocoder) and original yii2 tests
 */

namespace DevGroup\Multilingual\tests;

use DevGroup\Multilingual\tests\models\Post;
use Yii;
use yii\base\ExitException;
use yii\helpers\Url;
use yii\web\Application;
use yii\db\Connection;
use yii\web\ServerErrorHttpException;

/**
 * DatabaseTestCase
 */
class DatabaseTest extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * @inheritdoc
     */
    public function getConnection()
    {
        return $this->createDefaultDBConnection(\Yii::$app->getDb()->pdo);
    }

    /**
     * @inheritdoc
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(__DIR__ . '/data/test.xml');
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        (new \yii\web\Application([
            'id' => 'unit',
            'basePath' => __DIR__,
            'bootstrap' => ['multilingual'],
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ .'/index.php',
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
                    'default_language_id' => 1,
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
        try {
            Yii::$app->set('db', [
                'class' => Connection::className(),
                'dsn' => 'mysql:host=localhost;dbname=yii2_multilingual',
                'username' => 'root',
                'password' => '',
            ]);

            Yii::$app->getDb()->open();
            $lines = explode(';', file_get_contents(__DIR__ . '/migrations/mysql.sql'));

            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    Yii::$app->getDb()->pdo->exec($line);
                }
            }
        } catch (\Exception $e) {
            Yii::$app->clear('db');
        }


        if (Yii::$app->get('db', false) === null) {
            $this->markTestSkipped();
        } else {
            parent::setUp();
        }
    }

    /**
     * Clean up after test.
     * By default the application created with [[mockApplication]] will be destroyed.
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->destroyApplication();
    }
    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        if (\Yii::$app && \Yii::$app->has('session', true)) {
            \Yii::$app->session->close();
        }
        \Yii::$app = null;
    }

    public function testParseLang()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;

        Yii::$app->trigger(Application::EVENT_BEFORE_REQUEST);

        // test redirect from unknown host to geo-based
        $_SERVER['SERVER_NAME'] = 'unknown.host';
        $_SERVER['REQUEST_URI'] = '/';
        try {
            $this->resolve();
        } catch (ExitException $e) {
            $this->assertArraySubset(['location'=>['http://example.ru/']], Yii::$app->response->headers->toArray());
            $this->assertEquals(302, Yii::$app->response->statusCode);
            $this->assertEquals(2, $multilingual->language_id_geo);
        }

        // test good domain
        $_SERVER['SERVER_NAME'] = 'example.ru';
        $_SERVER['REQUEST_URI'] = '/';
        $this->resolve();

        $this->assertEquals(2, $multilingual->language_id_geo);
        $this->assertEquals(2, $multilingual->language_id);

        // geo = ru, domain != ru
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/';
        try {
            $this->resolve();
        } catch (ExitException $e) {
            $this->assertArraySubset(['location'=>['http://example.ru/']], Yii::$app->response->headers->toArray());
            $this->assertEquals(302, Yii::$app->response->statusCode);
            $this->assertEquals(2, $multilingual->language_id_geo);
            $this->assertEquals(2, $multilingual->language_id);
        }

        // geo = ru, domain != ru, proper eng folder
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en/';

        $this->resolve();

        // geo = ru
        $this->assertEquals(2, $multilingual->language_id_geo);
        // url requested geo = en
        $this->assertEquals(1, $multilingual->language_id);

        // geo = ru, domain != ru, proper eng folder and some url
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en/url/to?something=yes';

        $this->resolve();

        // geo = ru
        $this->assertEquals(2, $multilingual->language_id_geo);
        // url requested geo = en
        $this->assertEquals(1, $multilingual->language_id);

        // geo=ru, domain != ru, proper eng folder but without leading slash - should redirect
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en';
        try {
            $this->resolve();
        } catch (ExitException $e) {
            $this->assertArraySubset(['location'=>['http://example.com/en/']], Yii::$app->response->headers->toArray());
            $this->assertEquals(301, Yii::$app->response->statusCode);
            $this->assertEquals(2, $multilingual->language_id_geo);
            $this->assertEquals(1, $multilingual->language_id);
        }
    }

    public function testCreateUrl()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;

        Yii::$app->trigger(Application::EVENT_BEFORE_REQUEST);
        // geo = ru, domain != ru, proper eng folder and some url
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en/url/to?something=yes';
        $this->resolve();
        // geo = ru
        $this->assertEquals(2, $multilingual->language_id_geo);
        // url requested geo = en
        $this->assertEquals(1, $multilingual->language_id);

        $this->assertEquals('/en/site/about?id=1', Url::to(['/site/about', 'id'=>1]));
        $this->assertEquals('http://example.ru/site/about?id=1', Url::to(['/site/about', 'id'=>1, 'language_id' => 2]));
        $this->assertEquals('http://example.com/de/site/about?id=1', Url::to(['/site/about', 'id'=>1, 'language_id' => 3]));
        $this->assertEquals('http://example.com/en/site/about', Url::to(['/site/about'], true));

        $badLangTest = '';
        try {
            $badLangTest = Url::to(['/site/about', 'id'=>1, 'language_id'=>-1]);
        } catch (ServerErrorHttpException $e) {
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->assertTrue(false, 'Unknown exception');
        }
        $this->assertEmpty($badLangTest, 'Url should be empty, because of unexisting language');

        $urlManager = Yii::$app->urlManager;
        $this->assertEquals('/site/login', $urlManager->createUrl(['site/login']));
        $this->assertEquals('/en/site/test', $urlManager->createUrl(['site/test']));
        $urlManager->includeRoutes = ['site/included', 'site/another'];
        $this->assertEquals('/site/test', $urlManager->createUrl(['site/test']));
        $this->assertEquals('/en/site/included', $urlManager->createUrl(['site/included']));
        $urlManager->includeRoutes = [];
    }

    /**
     * Resets Yii2 Request component so it can handle another fake request and resolves it
     */
    private function resolve()
    {
        Yii::$app->set('request', [
            'class' => \yii\web\Request::className(),
            'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
            'scriptFile' => __DIR__ .'/index.php',
            'scriptUrl' => '/index.php',
        ]);;
        Yii::$app->request->resolve();
    }


    public function testRecords()
    {
        $posts = Post::find()->all();
        $this->assertEquals(4, count($posts));
    }
}
