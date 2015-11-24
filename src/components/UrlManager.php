<?php

namespace DevGroup\Multilingual\components;

use DevGroup\Multilingual\LanguageEvents\AfterGettingLanguage;
use DevGroup\Multilingual\LanguageEvents\GettingLanguage;
use DevGroup\Multilingual\LanguageEvents\languageEvent;
use DevGroup\Multilingual\models\Language;
use Yii;
use yii\web\ServerErrorHttpException;
use yii\web\UrlManager as BaseUrlManager;

class UrlManager extends BaseUrlManager
{

    const GET_LANGUAGE = 'get_language';
    const AFTER_GET_LANGUAGE = 'after_get_language';

    public $cache = 'cache';

    public $cacheLifetime = 86400;

    /** @var bool|array */
    public $includeRoutes = false;

    /** @var bool|array */
    public $excludeRoutes = [
        'site/login',
        'site/logout',
    ];

    public $languageParam = 'language_id';

    public $forceHostInUrl = false;

    public $enablePrettyUrl = true;

    public $showScriptName = false;

    public $rules = [
        '' => 'site/index',
    ];

    /** @var null|string null to set scheme as it is requested, string(http or https) for exact scheme forcing */
    public $forceScheme = null;

    /** @var null|integer null to set port as it is requested, integer(ie 8080) for exact port */
    public $forcePort = null;

    /**
     * @return \yii\caching\Cache
     */
    public function cache()
    {
        return Yii::$app->get($this->cache);
    }

    public $requestEvents = [
        'DevGroup\Multilingual\LanguageEvents\GettingLanguageByUrl',
        'DevGroup\Multilingual\LanguageEvents\GettingLanguageByCookie',
        'DevGroup\Multilingual\LanguageEvents\GettingLanguageByGeo',
        'DevGroup\Multilingual\LanguageEvents\GettingLanguageByUserInformation',
    ];

    /**
     * @inheritdoc
     */
    public function createUrl($params)
    {
        $params = (array)$params;
        $route = trim($params[0], '/');

        if ($this->excludeRoutes !== false) {
            if (in_array($route, $this->excludeRoutes)) {
                return parent::createUrl($params);
            }
        }

        if ($this->includeRoutes !== false) {
            if (in_array($route, $this->includeRoutes) === false) {
                return parent::createUrl($params);
            }
        }
        return $this->createLanguageUrl($params);

    }

    /**
     * Creates URL with language identifiers(domain and/or folder)
     * @param $params
     * @return string
     * @throws ServerErrorHttpException
     */
    private function createLanguageUrl($params)
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;

        $requested_language_id = isset($params[$this->languageParam]) ? $params[$this->languageParam] : null;
        if ($requested_language_id === null) {
            $requested_language_id = $multilingual->language_id;
        } else {
            unset($params[$this->languageParam]);
        }

        /** @var Language $requested_language */
        $requested_language = Language::findOne(['id' => $requested_language_id]);
        if ($requested_language === null) {
            throw new ServerErrorHttpException('Requested language not found');
        }
        $current_language_id = $multilingual->language_id;

        $url = parent::createUrl($params);
        if (!empty($requested_language->folder)) {
            $url = '/' . $requested_language->folder . '/' . ltrim($url, '/');
        }
        if ($current_language_id === $requested_language->id && $this->forceHostInUrl === false) {
            return $url;
        }

        if ($this->forceScheme !== null) {
            $scheme = $this->forceScheme;
        } else {
            $scheme = Yii::$app->request->getIsSecureConnection() ? 'https' : 'http';
        }

        if ($this->forcePort !== null) {
            $port = $this->forcePort === 80 ? '' : ':' . $this->forcePort;
        } else {
            $port = Yii::$app->request->port === 80 ? '' : ':' . Yii::$app->request->port;
        }
        return $scheme . '://' . $requested_language->domain . $port . '/' . ltrim($url, '/');
    }

    /**
     * @return string Requested domain
     */
    public function requestedDomain()
    {
        return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : Yii::$app->request->serverName;
    }

    /**
     * @inheritdoc
     */
    public function parseRequest($request)
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;
        foreach ($this->requestEvents as $filter) {
            if (is_subclass_of($filter, GettingLanguage::class)) {
                $this->on(self::GET_LANGUAGE, [$filter, 'gettingLanguage']);
            }
            if (is_subclass_of($filter, AfterGettingLanguage::class)) {
                $this->on(self::AFTER_GET_LANGUAGE, [$filter, 'afterGettingLanguage']);
            }
        }
        $event = new languageEvent();
        $event->multilingual = $multilingual;
        $event->domain = $this->requestedDomain();
        $event->request = $request;
        $event->languages = array_reduce(
            Language::find()->all(),
            function ($arr, $i) {
                $arr[$i->id] = $i;
                return $arr;
            },
            []
        );
        $this->trigger(self::GET_LANGUAGE, $event);

        $multilingual->language_id = $event->currentLanguageId ?
            $event->currentLanguageId :
            $multilingual->default_language_id;

        /** @var bool|Language $languageMatched */
        $languageMatched = $event->languages[$multilingual->language_id];

        Yii::$app->language = $languageMatched->yii_language;
        $path = explode('/', $request->pathInfo);
        $folder = array_shift($path);

        if (is_array($this->excludeRoutes)) {
            $resolved = parent::parseRequest($request);
            if (is_array($resolved)) {
                $route = reset($resolved);
                if (in_array($route, $this->excludeRoutes)) {
                    $multilingual->language_id = $multilingual->cookie_language_id;
                    /** @var Language $lang */
                    $lang = Language::findOne($multilingual->cookie_language_id);
                    Yii::$app->language = $lang->yii_language;
                    return $resolved;
                }
            }
        }

        $this->trigger(self::AFTER_GET_LANGUAGE, $event);

        if ($event->redirectUrl !== false && $event->redirectCode !== false) {
            Yii::$app->response->redirect($event->redirectUrl, $event->redirectCode, false);
            Yii::$app->end();
        }
        if (!empty($languageMatched->folder)) {
            $request->setPathInfo(implode('/', $path));
        }

        return parent::parseRequest($request);
    }
}