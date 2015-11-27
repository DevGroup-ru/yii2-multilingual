<?php
/**
 * This unit tests are based on work of Alexander Kochetov (@creocoder) and original yii2 tests
 */

namespace DevGroup\Multilingual\tests;

use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\tests\models\AllPost;
use DevGroup\Multilingual\tests\models\AllPostNoTrait;
use DevGroup\Multilingual\tests\models\Post;
use DevGroup\Multilingual\tests\models\PostTranslation;
use DevGroup\Multilingual\tests\models\PostNoScope;
use DevGroup\Multilingual\widgets\HrefLang;
use Yii;
use yii\base\ExitException;
use yii\helpers\Url;
use yii\web\Application;
use yii\db\Connection;
use yii\web\Cookie;
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
                'dsn' => 'mysql:host=localhost;dbname=multilingual.dev',
                'username' => 'root',
                'password' => '7896321',
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
        $needsException = true;
        try {
            $this->resolve();
            $needsException = false;
        } catch (ExitException $e) {
            $this->assertArraySubset(['location' => ['http://example.ru/']], Yii::$app->response->headers->toArray());
            $this->assertEquals(302, Yii::$app->response->statusCode);
            $this->assertEquals(2, $multilingual->language_id_geo);
        }
        $this->assertTrue($needsException);


        // test good domain
        $_SERVER['SERVER_NAME'] = 'example.ru';
        $_SERVER['REQUEST_URI'] = '/';
        $this->resolve();

        $this->assertEquals(2, $multilingual->language_id_geo);
        $this->assertEquals(2, $multilingual->language_id);

        // geo = ru, domain != ru
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/';
        $needsException = true;
        try {
            $this->resolve();
            $needsException = false;
        } catch (ExitException $e) {
            $this->assertArraySubset(['location' => ['http://example.ru/']], Yii::$app->response->headers->toArray());
            $this->assertEquals(302, Yii::$app->response->statusCode);
            $this->assertEquals(2, $multilingual->language_id_geo);
            $this->assertEquals(2, $multilingual->language_id);
        }
        $this->assertTrue($needsException);

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
        $needsException = true;
        try {
            $this->resolve();
            $needsException = false;
        } catch (ExitException $e) {
            $this->assertArraySubset(['location' => ['http://example.com/en/']],
                Yii::$app->response->headers->toArray());
            $this->assertEquals(301, Yii::$app->response->statusCode);
            $this->assertEquals(2, $multilingual->language_id_geo);
            $this->assertEquals(1, $multilingual->language_id);
        }
        $this->assertTrue($needsException);
    }

    public function testParseByCookie()
    {
        $multilingual = Yii::$app->multilingual;

        $_COOKIE['language_id'] = Yii::$app->getSecurity()->hashData(
            serialize(['language_id', 2]),
            Yii::$app->request->cookieValidationKey
        );
        // geo = ru, domain != ru, proper eng folder
        $_SERVER['SERVER_NAME'] = 'unknown.host';
        $_SERVER['REQUEST_URI'] = '/';
        $needsException = true;
        try {
            $this->resolve();
            $needsException = false;
        } catch (ExitException $e) {
            $multilingual->retrieveCookieLanguage();
            $this->assertEquals(302, Yii::$app->response->statusCode);
            $this->assertEquals(2, $multilingual->language_id);
            $this->assertEquals(2, $multilingual->cookie_language_id);
        }
        $this->assertTrue($needsException);
    }

    public function testParseByUser()
    {
        $multilingual = Yii::$app->multilingual;
        $_SERVER['HTTP_CLIENT_IP'] = '117.104.133.167'; //jp
        $_SERVER['SERVER_NAME'] = 'unknown.host';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4';
        $needsException = true;
        try {
            $this->resolve();
            $needsException = false;
        } catch (ExitException $e) {
            $this->assertEquals(2, $multilingual->language_id);
        }
        $this->assertTrue($needsException);

        $_SERVER['HTTP_CLIENT_IP'] = '117.104.133.167'; //jp
        $_SERVER['SERVER_NAME'] = 'unknown.host';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,ru;q=0.8,ru-RU;q=0.6,en;q=0.4';
        $needsException = true;
        try {
            $this->resolve();
            $needsException = false;
        } catch (ExitException $e) {
            $this->assertEquals(1, $multilingual->language_id);
        }
        $this->assertTrue($needsException);

    }

    public function testNeedConfirmation()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;


        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en/';
        $this->assertFalse($multilingual->needsConfirmation);

        $_SERVER['HTTP_CLIENT_IP'] = '117.104.133.167'; //jp
        $_SERVER['SERVER_NAME'] = 'unknown.host';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4';
        $needsException = true;
        try {
            $this->resolve();
            $needsException = false;
        } catch (ExitException $e) {
            $this->assertTrue($multilingual->needsConfirmation);
        }
        $this->assertTrue($needsException);
    }

    public function testNeedPreferredLanguage()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en/';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4';

        $this->resolve();

        $this->assertEquals(1, $multilingual->language_id);
        $this->assertEquals(2, $multilingual->preferred_language_id);
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

        $this->assertEquals('/en/site/about?id=1', Url::to(['/site/about', 'id' => 1]));
        $this->assertEquals('http://example.ru/site/about?id=1',
            Url::to(['/site/about', 'id' => 1, 'language_id' => 2]));
        $this->assertEquals('http://example.com/de/site/about?id=1',
            Url::to(['/site/about', 'id' => 1, 'language_id' => 3]));
        $this->assertEquals('http://example.com/en/site/about', Url::to(['/site/about'], true));

        $badLangTest = '';
        try {
            $badLangTest = Url::to(['/site/about', 'id' => 1, 'language_id' => -1]);
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


    public function testCityEmpty()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en/';

        $multilingual->retrieveInfo();
        /** no Ip data */
        $result = $multilingual->getPreferredCity();
        $this->assertEquals(null, $result);
        $this->assertTrue($multilingual->cityNeedsConfirmation);


    }

    public function testCityCookie()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;
        /** isset cookie */
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en/';


        Yii::$app->request->cookies->readOnly = false;
        Yii::$app->request->cookies->add(new Cookie([
            'name' => 'city_id',
            'value' => 2
        ]));

        $multilingual->retrieveInfo();
        $result = $multilingual->getPreferredCity();
        $this->assertEquals(2, $result->getId());
        $this->assertFalse($multilingual->cityNeedsConfirmation);
    }

    public function testCityByGeo()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en/';
        $multilingual->handlers[0]['default']['city'] = [
            'iso' => null,
            'name' => 'Tambov',
            'lat' => 52.73169,
            'lon' => 41.44326
        ];

        $multilingual->retrieveInfo();
        $this->resolve();

        $result = $multilingual->getPreferredCity();
        $this->assertEquals(1, $result->getId());
        $this->assertFalse($multilingual->cityNeedsConfirmation);


    }

    public function testCityByGeoNearest()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en/';
        $multilingual->handlers[0]['default']['city'] = [
            'iso' => null,
            'name' => 'Michurinsk',
            'lat' => 52.8978,
            'lon' => 40.4907
        ];
        $multilingual->retrieveInfo();
        $this->resolve();

        $result = $multilingual->getPreferredCity();
        $this->assertEquals(1, $result->getId());
        $this->assertTrue($multilingual->cityNeedsConfirmation);
    }


    public function testCityARModel()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;
        $model = call_user_func(
            [
                $multilingual->modelsMap['City'],
                'getById'
            ],
            2
        );
        $this->assertEquals('Voronezh', $model->getName());

        $count = count(call_user_func([$multilingual->modelsMap['City'], 'getAll']));
        $this->assertEquals(3, $count);
    }


    /**
     * Resets Yii2 Request component so it can handle another fake request and resolves it
     */
    private function resolve()
    {
        Yii::$app->set('request', [
            'class' => \yii\web\Request::className(),
            'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
            'scriptFile' => __DIR__ . '/index.php',
            'scriptUrl' => '/index.php',
        ]);
        return Yii::$app->request->resolve();
    }


    public function testRecords()
    {
        Yii::$app->trigger(Application::EVENT_BEFORE_REQUEST);
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en/url/to?something=yes';
        $this->resolve();

        /** @var Post|PostTranslation|MultilingualActiveRecord $post */
        // all published records in eng
        $posts = Post::find()->all();
        $this->assertEquals(3, count($posts));
        foreach ($posts as $post) {
            $this->assertEquals(1, $post->is_published);
        }

        // the same as above but without applyDefaultScope in the model
        $posts = PostNoScope::find()->all();
        $this->assertEquals(3, count($posts));
        foreach ($posts as $post) {
            $this->assertEquals(1, $post->is_published);
        }

        // all posts including unpublished in eng
        $posts = AllPost::find()->all();
        $this->assertEquals(5, count($posts));

        // find post with all translations
        $post = Post::findOne(1);
        $this->assertTrue($post->hasTranslation(1));
        $this->assertTrue($post->hasTranslation(2));
        $this->assertTrue($post->hasTranslation(3));
        $this->assertFalse($post->hasTranslation(4));

        // find post without published en translation
        $post = Post::findOne(4);
        // by-default as we have innerjoin trait the post will not be found as it doen't has en published translation
        $this->assertNull($post);

        // same but without published state
        $post = AllPost::findOne(4);
        $this->assertNotNull($post);
        $this->assertTrue($post->hasTranslation(1));
        $this->assertFalse($post->hasTranslation(4));

        // find post without existing en translation
        $post = AllPostNoTrait::findOne(6);
        $this->assertNotNull($post);
        $this->assertFalse($post->hasTranslation(1));
        $this->assertFalse($post->hasTranslation());
        $this->assertFalse($post->hasTranslation(2));
        $this->assertTrue($post->hasTranslation(3));
        $this->assertFalse($post->hasTranslation(4));
        /** @var PostTranslation $translation */
        $translation = $post->translate(3);
        $this->assertEquals($translation->is_published, 1);
        $this->assertEquals($translation->title, 'Post titel 6');

        $translation = $post->translate(1);


        $this->assertTrue($translation->isNewRecord);
        $this->assertEquals($translation->is_published, 1);
        $this->assertEquals($translation->title, '');
        $this->assertEquals($post->title, '');
        $this->assertFalse($post->save());

        $this->assertArrayHasKey('translations', $post->errors);
        $this->assertArrayHasKey('title', $translation->errors);

        $post->title = 'New post 6 title';
        $post->body = 'New post 6 body';
        $this->assertEquals($translation->title, 'New post 6 title');
        $this->assertEquals($post->title, 'New post 6 title');
        $this->assertTrue($post->save());


        $this->assertEquals(1, $post->delete());
        $this->assertNull(AllPostNoTrait::findOne(6));
        $this->assertNull(PostTranslation::findOne(['model_id' => 6, 'language_id' => 3]));


        // find post with en but without ru and unpublished de
        $post = Post::findOne(5);
        $this->assertTrue($post->hasTranslation(1));
        $this->assertFalse($post->hasTranslation(2));
        $this->assertFalse($post->hasTranslation(3));
        $this->assertFalse($post->hasTranslation(4));
        $this->assertTrue($post->hasTranslation());


    }

    public function testHreflang()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;
        // test good domain
        $_SERVER['SERVER_NAME'] = 'example.ru';
        $_SERVER['REQUEST_URI'] = '/site/index';

        Yii::$app->trigger(Application::EVENT_BEFORE_REQUEST);
        $this->resolve();

        Yii::$app->handleRequest(Yii::$app->request);

        $this->assertEquals(2, $multilingual->language_id_geo);
        $this->assertEquals(2, $multilingual->language_id);
        Yii::$app->controller = Yii::$app->createController('/site')[0];
        $expected = '<link href="http://example.com/en/" rel="alternate" hreflang="en">
<link href="http://example.com/de/" rel="alternate" hreflang="de">
';
        $this->assertEquals($expected, HrefLang::widget());

        // test another url
        $_SERVER['REQUEST_URI'] = '/site/about';

        $this->resolve();
        Yii::$app->handleRequest(Yii::$app->request);
        $this->assertEquals(2, $multilingual->language_id_geo);
        $this->assertEquals(2, $multilingual->language_id);

        $expected = '<link href="http://example.com/en/site/about" rel="alternate" hreflang="en">
<link href="http://example.com/de/site/about" rel="alternate" hreflang="de">
';
        $this->assertEquals($expected, HrefLang::widget());
    }
}
