<?php

namespace DevGroup\Multilingual\components;

use DevGroup\Multilingual\LanguageEvents\AfterGettingLanguage;
use DevGroup\Multilingual\LanguageEvents\GettingLanguage;
use DevGroup\Multilingual\LanguageEvents\LanguageEvent;
use DevGroup\Multilingual\models\Language;
use Yii;
use yii\web\ServerErrorHttpException;
use yii\web\UrlManager as BaseUrlManager;

class UrlManager extends BaseUrlManager
{

    const GET_LANGUAGE = 'get_language';
    const AFTER_GET_LANGUAGE = 'after_get_language';
    const GET_PREFERRED_LANGUAGE = 'get_preferred_language';

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
        $languages = $multilingual->getAllLanguages();

        foreach ($multilingual->requestedLanguageEvents as $filter) {
            if (is_subclass_of($filter, GettingLanguage::class)) {
                $this->on(self::GET_LANGUAGE, [$filter, 'gettingLanguage']);
            }
            if (is_subclass_of($filter, AfterGettingLanguage::class)) {
                $this->on(self::AFTER_GET_LANGUAGE, [$filter, 'afterGettingLanguage']);
            }
        }
        foreach ($multilingual->preferredLanguageEvents as $filter) {
            if (is_subclass_of($filter, GettingLanguage::class)) {
                $this->on(self::GET_PREFERRED_LANGUAGE, [$filter, 'gettingLanguage']);
            }
        }
        $eventRequestedLanguage = new LanguageEvent();
        $eventRequestedLanguage->multilingual = $multilingual;
        $eventRequestedLanguage->domain = $this->requestedDomain();
        $eventRequestedLanguage->request = $request;
        $eventRequestedLanguage->languages = $languages;
        $this->trigger(self::GET_LANGUAGE, $eventRequestedLanguage);

        $multilingual->language_id = $eventRequestedLanguage->currentLanguageId ?
            $eventRequestedLanguage->currentLanguageId :
            $multilingual->default_language_id;

        /** @var bool|Language $languageMatched */
        $languageMatched = $languages[$multilingual->language_id];

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

        $eventPreferredLanguage = new LanguageEvent();
        $eventPreferredLanguage->multilingual = $multilingual;
        $eventPreferredLanguage->domain = $this->requestedDomain();
        $eventPreferredLanguage->request = $request;
        $eventPreferredLanguage->languages = $languages;


        $this->trigger(self::GET_PREFERRED_LANGUAGE, $eventPreferredLanguage);
        $multilingual->preferred_language_id = $eventPreferredLanguage->currentLanguageId ?
            $eventPreferredLanguage->currentLanguageId :
            $eventRequestedLanguage->currentLanguageId;

        $this->trigger(self::AFTER_GET_LANGUAGE, $eventRequestedLanguage);

        if (in_array(
                $eventRequestedLanguage->resultClass,
                $multilingual->needConfirmationEvents
            ) ||
            $eventRequestedLanguage->resultClass === null ||
            Yii::$app->session->getFlash('needsConfirmation', false)
        ) {
            $multilingual->needsConfirmation = true;
        }

        if ($eventRequestedLanguage->redirectUrl !== false && $eventRequestedLanguage->redirectCode !== false) {
            if ($multilingual->needsConfirmation) {
                Yii::$app->session->setFlash('needsConfirmation', true);
            }
            Yii::$app->response->redirect(
                $eventRequestedLanguage->redirectUrl,
                $eventRequestedLanguage->redirectCode,
                false
            );
            Yii::$app->end();
        }


        if (!empty($languageMatched->folder)) {
            $request->setPathInfo(implode('/', $path));
        }

        return parent::parseRequest($request);
    }
}