<?php

namespace DevGroup\Multilingual\tests;

use DevGroup\Multilingual\Multilingual;
use yii\base\ExitException;
use yii\web\Application;
use Yii;

class ContextTest extends MultilingualTestsInit
{
    public function testFirstContext()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;

        Yii::$app->trigger(Application::EVENT_BEFORE_REQUEST);

        // context = 1 lang = en
        $_SERVER['SERVER_NAME'] = 'example.ru';
        $_SERVER['REQUEST_URI'] = '/';
        $this->resolve();
        $this->assertEquals(2, $multilingual->language_id);
        $this->assertEquals(1, $multilingual->context_id);
        $this->assertEquals(3, count($multilingual->getAllLanguages()));
    }

    public function testSecondContext()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;

        Yii::$app->trigger(Application::EVENT_BEFORE_REQUEST);

        // context = 2 lang = ru
        $_SERVER['SERVER_NAME'] = 'example.org';
        $_SERVER['REQUEST_URI'] = '/';
        try {
            $this->resolve();
        } catch (ExitException $e) {
            $this->assertEquals(2, $multilingual->language_id);
            $this->assertEquals(2, $multilingual->context_id);
        }

        // context = 2 lang = en
        $_SERVER['SERVER_NAME'] = 'en.example.org';
        $_SERVER['REQUEST_URI'] = '/';
        $this->resolve();
        $this->assertEquals(1, $multilingual->language_id);
        $this->assertEquals(2, $multilingual->context_id);
        $languages = $multilingual->getAllLanguages();
        $defaultLanguage = reset($languages);
        $this->assertEquals(3, count($languages));
        $this->assertEquals(1, $defaultLanguage->id);
    }

    /**
     * Делаем запрос на несуществующий контекст - получаем 404
     *
     * @expectedException \yii\web\NotFoundHttpException
     */
    public function testFakeContext()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        Yii::$app->trigger(Application::EVENT_BEFORE_REQUEST);
        $_SERVER['SERVER_NAME'] = 'de.example.org';
        $_SERVER['REQUEST_URI'] = '/';
        $this->resolve();
    }

    /**
     *  - контекст ни к чему не привязан (example.org)
     *  - установлен HTTP_ACCEPT_LANGUAGE (en-US,en;q=0.8)
     *  - запрашиваем дефолтный домен без языка (example.org)
     *  - имеем поддомен для приоритетного языка (en.example.org)
     *
     * Должны получить редирект на en.example.org
     */
    public function testPreferredContextWithDefinedLangs()
    {
        Yii::$app->trigger(Application::EVENT_BEFORE_REQUEST);
        /** @var Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;
        $_SERVER['SERVER_NAME'] = 'example.org';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.8';
        try {
            $this->resolve();
        } catch (ExitException $e) {
            $this->assertEquals(302, Yii::$app->response->statusCode);
            $this->assertEquals(1, $multilingual->language_id);
            $this->assertEquals(2, $multilingual->context_id);
            $this->assertArraySubset(
                ['location' => ['http://en.example.org/']],
                Yii::$app->response->getHeaders()->toArray()
            );
        }
    }

    /**
     * - контекст привязан к языку (example.net) - русский
     * - установлен HTTP_ACCEPT_LANGUAGE (en-US,en;q=0.8)
     * - запрашиваем дефолтный (example.net)
     * - язык запрошенного домена не совпадает с предпочитаемым языком
     *
     * Получаем запрос подтверждения редиректа
     */
    public function testPreferredContextWithGeo()
    {
        Yii::$app->trigger(Application::EVENT_BEFORE_REQUEST);
        /** @var Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;
        $_SERVER['SERVER_NAME'] = 'example.net';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.8';
        $this->resolve();
        $this->assertEquals(true, $multilingual->needsConfirmation);
        $this->assertEquals(2, $multilingual->language_id);
        $this->assertEquals(3, $multilingual->context_id);
    }
}