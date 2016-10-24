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
use Yii;
use yii\web\Application;
use yii\db\Connection;

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
}
