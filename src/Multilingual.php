<?php

namespace DevGroup\Multilingual;

use DevGroup\Multilingual\models\CountryLanguage;
use DevGroup\Multilingual\models\Language;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Cookie;

class Multilingual extends Component implements BootstrapInterface
{
    /** @var bool Use X-Forwarded-For for ip detection */
    public $useXForwardedFor = false;
    /** @var bool Use CLIENT_IP header for ip detection */
    public $useClientIp = false;

    /**
     * @var bool|string String if we need to set mock ip, false to use real
     */
    public $mockIp = false;

    /** @var GeoInfo */
    protected $geo = null;

    /** @var null|integer User language ID determined by ip-geo information */
    public $language_id_geo = null;

    /** @var null|int User language ID determined by URL */
    public $language_id = null;

    /** @var null|int Language ID from his cookie */
    public $cookie_language_id = null;

    /** @var bool The case when geo information is ok, but no match for country->app-language */
    public $geo_default_language_forced = false;

    /**
     * ID of default site language.
     * WARNING! You can probably have big problems(in Console application for example) if you don't set this property!
     * @var null|int
     */
    public $default_language_id = null;

    /** @var string Application cache component name */
    public $cache = 'cache';
    /**
     * @var int Cache lifetime in seconds. Defaults to 2 weeks(1209600).
     */
    public $cacheLifetime = 1209600;

    /**
     * Chain of Geo handlers.
     *
     * @var array
     */
    public $handlers = [
        [
            'class' => 'DevGroup\Multilingual\DefaultGeoProvider',
            'default' => [
                'country' => [
                    'name' => 'Russia',
                    'iso' => 'ru',
                ],
            ],
        ]
    ];
    public function init()
    {
        parent::init();
        $this->language_id = $this->default_language_id;
    }

    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param Application $app the application currently running
     */
    public function bootstrap($app)
    {

        $app->on(Application::EVENT_BEFORE_REQUEST, function () {
            $this->retrieveInfo();
            $this->retrieveLanguageFromGeo();
            $this->retrieveCookieLanguage();
        });

    }

    /**
     * @return string IP where user is located
     */
    private function getIp()
    {
        if ($this->mockIp !== false) {
            return $this->mockIp;
        }
        $validator = new \DevGroup\Multilingual\validators\IpValidator;
        $validator->ipv4 = true;
        if ($this->useClientIp === true && isset($_SERVER['HTTP_CLIENT_IP'])) {
            if ($validator->validate($_SERVER['HTTP_CLIENT_IP'])) {
                return $_SERVER['HTTP_CLIENT_IP'];
            }
        }
        if ($this->useXForwardedFor === true && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            if ($validator->validate($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        }
        return Yii::$app->request->userIP;
    }

    /**
     * Runs Geo retrievers chain for getting GEO information by ip
     * Fills $this->geo
     */
    public function retrieveInfo()
    {
        $ip = $this->getIp();

        Yii::beginProfile('Retrieving of geo ip info');

        if ($this->cache !== false) {
            $this->geo = $this->cache()->get("GeoIP:$ip");
            if ($this->geo === false) {
                $this->geo = null;
            } else {
                Yii::endProfile('Retrieving of geo ip info');
                return;
            }
        }

        foreach ($this->handlers as $handler) {
            /** @var GeoProviderInterface $object */
            $object = Yii::createObject($handler);
            $profile_name = 'Handler: ' . get_class($object);
            Yii::beginProfile($profile_name);
            $info = $object->getGeoInfo($ip);
            if ($info instanceof GeoInfo &&
                (
                    $info->country->iso_3166_1_alpha_2 ||
                    $info->country->iso_3166_1_alpha_3 ||
                    $info->country->name
                )
            ) {
                $info->ip = $ip;
                $this->geo = $info;

                if ($this->cache !== false) {
                    $this->cache()->set("GeoIP:$ip", $this->geo, $this->cacheLifetime);
                }

                Yii::endProfile($profile_name);
                Yii::endProfile('Retrieving of geo ip info');
                return;
            }
            Yii::endProfile($profile_name);
        }
        Yii::endProfile('Retrieving of geo ip info');

    }

    /**
     * Retrieves user language based on his geo params(ip)
     */
    public function retrieveLanguageFromGeo()
    {
        // ok we have at least geo object, try to find language for it
        if ($this->geo instanceof GeoInfo) {
            $country = $this->geo->country;
            $searchOrder = [
                'iso_3166_1_alpha_2',
                'iso_3166_1_alpha_3',
                'name',
            ];
            foreach ($searchOrder as $attribute) {
                if (isset($country->$attribute)) {
                    $model = CountryLanguage::find()
                        ->where([$attribute => $country->$attribute])
                        ->one();
                    if ($model !== null) {
                        $this->language_id_geo = $model->language_id;
                        return;
                    }
                }
            }
        }
        $this->geo_default_language_forced = true;
        $this->language_id_geo = $this->default_language_id;
    }

    /**
     * Retrieves language form cookie
     */
    public function retrieveCookieLanguage()
    {
        if (Yii::$app->request->cookies->has('language_id')) {
            $language_id = intval(Yii::$app->request->cookies->get('language_id')->value);
            if (Language::findOne($language_id) !== null) {
                $this->cookie_language_id = $language_id;
            }
        }
        if ($this->cookie_language_id === null) {

            $this->cookie_language_id = $this->language_id;

            Yii::$app->response->cookies->add(new Cookie([
                'name' => 'language_id',
                'value' => $this->cookie_language_id,
            ]));
        }
    }

    /**
     * @return \yii\caching\Cache
     */
    public function cache()
    {
        return Yii::$app->get($this->cache);
    }

    /** @var GeoInfo */
    public function geo()
    {
        return $this->geo;
    }

    /**
     * Returns URL for current request translated on specified language
     * @param int $language_id
     *
     * @return string
     */
    public function translateCurrentRequest($language_id)
    {
        $params = ArrayHelper::merge(
            [Yii::$app->requestedRoute],
            Yii::$app->request->getQueryParams(),
            [
                'language_id' => $language_id,
            ]
        );
        return Url::to($params);
    }

}