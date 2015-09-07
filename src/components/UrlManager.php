<?php

namespace DevGroup\Multilingual\components;

use DevGroup\Multilingual\models\Language;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\UrlManager as BaseUrlManager;

class UrlManager extends BaseUrlManager
{

    public $cache = 'cache';

    public $cacheLifetime = 86400;

    /** @var bool|array  */
    public $includeRoutes = false;

    /** @var bool|array  */
    public $excludeRoutes = [
        'site/login',
        'site/logout',
    ];

    public $languageParam = 'language_id';

    private $force_host_in_url = false;

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
        $params = (array) $params;
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

    private function createLanguageUrl($params)
    {

        $requested_language_id = isset($params[$this->languageParam]) ? $params[$this->languageParam] : null;
        if ($requested_language_id === null && $this->force_host_in_url === false) {
            return parent::createUrl($params);
        }
        unset($params[$this->languageParam]);

        /** @var Language $requested_language */
        $requested_language = Language::findOne(['id' => $requested_language_id]);
        if ($requested_language === null) {
            throw new \yii\web\ServerErrorHttpException('Requested language not found');
        }
        $current_language_id = Yii::$app->multilingual->language_id;

        $url = parent::createUrl($params);
        if (!empty($requested_language->folder)) {
            $url = '/' . $requested_language->folder .'/' . ltrim($url, '/');
        }
        if ($current_language_id === $requested_language && $this->force_host_in_url === false) {
            return $url;
        }

        $scheme = Yii::$app->request->getIsSecureConnection() ? 'https' : 'http';
        $port = Yii::$app->request->port === 80 ? '' : ':' . Yii::$app->request->port;
        return $scheme . '://' . $requested_language->domain . $port . '/' . ltrim($url, '/');
    }

    private function requestedDomain()
    {
        return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : Yii::$app->request->serverName;;
    }

    /**
     * @inheritdoc
     */
    public function parseRequest($request)
    {
        $domain = $this->requestedDomain();

        $path = explode('/', $request->pathInfo);
        $folder = array_shift($path);
        $languages = Language::find()->all();
        /** @var bool|Language $languageMatched */
        $languageMatched = false;
        foreach ($languages as $language) {
            $matchedDomain = $language->domain === $domain;
            if (empty($language->folder)) {
                $matchedFolder = $matchedDomain;
            } else {
                $matchedFolder = $language->folder === $folder;
            }
            if ($matchedDomain && $matchedFolder) {
                $languageMatched = $language;
                Yii::$app->multilingual->language_id_url = $language->id;
                break;
            }
        }
        if ($languageMatched === false) {
            // no matched language - should redirect to user's regional domain with 302
            /** @var \DevGroup\Multilingual\Multilingual $multilingual */
            $multilingual = Yii::$app->multilingual;
            $this->force_host_in_url = true;
            $url = $this->createUrl([$request->pathInfo, 'language_id' => $multilingual->language_id]);
            Yii::$app->response->redirect($url, 302, false);
            Yii::$app->end();
        }
        if ($languageMatched !== false && !empty($languageMatched->folder)) {
            // matched language urls are made with subfolders
            // cut them down(path was already shifted)
            $request->setPathInfo(implode('/', $path));
        }


        return parent::parseRequest($request);
    }
}