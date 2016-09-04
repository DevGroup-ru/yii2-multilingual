<?php

namespace DevGroup\Multilingual\tests;

use DevGroup\Multilingual\widgets\HrefLang;
use Yii;
use yii\base\ExitException;
use yii\helpers\Url;
use yii\web\Application;
use yii\web\Cookie;
use yii\web\ServerErrorHttpException;

class CommonTest  extends MultilingualTestsInit
{
    public function testParseLang()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;

        Yii::$app->trigger(Application::EVENT_BEFORE_REQUEST);

        // test good domain
        $_SERVER['SERVER_NAME'] = 'example.ru';
        $_SERVER['REQUEST_URI'] = '/';
        $this->resolve();

        $this->assertEquals('rus', $multilingual->iso_639_2t_geo);
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
            $this->assertEquals('rus', $multilingual->iso_639_2t_geo);
            $this->assertEquals(2, $multilingual->language_id);
        }
        $this->assertTrue($needsException);

        // geo = ru, domain != ru, proper eng folder
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en/';

        $this->resolve();

        // geo = ru
        $this->assertEquals('rus', $multilingual->iso_639_2t_geo);
        // url requested geo = en
        $this->assertEquals(1, $multilingual->language_id);


        // geo = ru, domain != ru, proper eng folder and some url
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en/url/to?something=yes';

        $this->resolve();

        // geo = ru
        $this->assertEquals('rus', $multilingual->iso_639_2t_geo);
        // url requested geo = en
        $this->assertEquals(1, $multilingual->language_id);

        // geo=ru, domain != ru, proper eng folder but without trailing slash - should redirect
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
            $this->assertEquals('rus', $multilingual->iso_639_2t_geo);
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
        $_SERVER['SERVER_NAME'] = 'example.com';
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
        $_SERVER['SERVER_NAME'] = 'example.com';
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
        $_SERVER['SERVER_NAME'] = 'example.com';
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
        $this->assertEquals('rus', $multilingual->iso_639_2t_geo);
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


    public function testCityByGeoFar()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en/';
        $multilingual->handlers[0]['default']['city'] = [
            'iso' => null,
            'name' => 'Reston',
            'lat' => 38.96872,
            'lon' => -77.3411
        ];
        $multilingual->retrieveInfo();
        $this->resolve();

        $result = $multilingual->getPreferredCity();
        $this->assertEquals(null, $result);
        $this->assertTrue($multilingual->cityNeedsConfirmation);
    }

    public function testPreferredCountry()
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
        $model = $multilingual->getPreferredCountry();
        $this->assertEquals(1, $model->id);
    }


    public function testPreferredCountryByGeo()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en/';
        $multilingual->handlers[0]['default']['country'] = [
            'name' => 'England',
            'name_native' => 'England',
            'language_id' => 5,
            'iso_3166_1_alpha_2' => 'en',
            'iso_3166_1_alpha_3' => 'eng',
        ];
        $multilingual->retrieveInfo();
        $multilingual->needsDetectCity = false;
        $this->resolve();
        $model = $multilingual->getPreferredCountry();
        $this->assertEquals(5, $model->id);
        $this->assertEquals(null, $multilingual->getPreferredCity());
    }

    public function testGetData()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        Yii::$app->trigger(Application::EVENT_BEFORE_REQUEST);
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/site/about?post=1';
        $_GET = ['post' => 1];
        try {
            $this->resolve();
        } catch (ExitException $e) {
            $this->assertArraySubset(
                ['location' => ['http://example.ru/site/about?post=1']],
                Yii::$app->response->getHeaders()->toArray()
            );
            $this->assertEquals(302, Yii::$app->response->statusCode);
        }

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

        $this->assertEquals('rus', $multilingual->iso_639_2t_geo);
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
        $this->assertEquals('rus', $multilingual->iso_639_2t_geo);
        $this->assertEquals(2, $multilingual->language_id);

        $expected = '<link href="http://example.com/en/site/about" rel="alternate" hreflang="en">
<link href="http://example.com/de/site/about" rel="alternate" hreflang="de">
';
        $this->assertEquals($expected, HrefLang::widget());
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
}