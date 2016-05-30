<?php

namespace DevGroup\Multilingual;

use DevGroup\Multilingual\models\CityInterface;
use DevGroup\Multilingual\models\Context;
use DevGroup\Multilingual\models\CountryLanguageInterface;
use DevGroup\Multilingual\models\Language;
use DevGroup\Multilingual\models\LanguageInterface;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Cookie;

/**
 * Class Multilingual
 *
 * @property integer|null $contextId
 * @package DevGroup\Multilingual
 */
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

    /** @var null|integer User language iso-code determined by ip-geo information */
    public $iso_639_2t_geo = null;

    /** @var null|int User language ID determined by requested Language Events */
    public $language_id = null;

    /** @var null|int User language ID determined by preferred Language Events */
    public $preferred_language_id = null;

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
            'class' => 'DevGroup\Multilingual\geoProviders\DefaultGeoProvider',
            'default' => [
                'country' => [
                    'name' => 'Russia',
                    'iso' => 'ru',
                ],
            ],
        ]
    ];

    /**
     * @var bool needs Confirmation requested language?
     */
    public $needsConfirmation = false;

    /**
     * @var bool needs detect city?
     */
    public $needsDetectCity = true;

    /**
     * @var bool needs Confirmation user City?
     */
    public $cityNeedsConfirmation = false;

    /***
     * @var int max distance in km between user geo information and needed city's
     */
    public $cityMaxDistance = 150;

    /**
     * @var array array of confirmation Events
     */
    public $needConfirmationEvents = [
        'DevGroup\Multilingual\LanguageEvents\GettingLanguageByGeo',
        'DevGroup\Multilingual\LanguageEvents\GettingLanguageByUserInformation',
    ];
    /**
     * @var array array of requested language Events
     */
    public $requestedLanguageEvents = [
        'DevGroup\Multilingual\LanguageEvents\GettingLanguageByUrl',
        'DevGroup\Multilingual\LanguageEvents\GettingLanguageByCookie',
        'DevGroup\Multilingual\LanguageEvents\GettingLanguageByUserInformation',
        'DevGroup\Multilingual\LanguageEvents\GettingLanguageByGeo',

    ];
    /**
     * @var array array of preferred language Events
     */
    public $preferredLanguageEvents = [
        'DevGroup\Multilingual\LanguageEvents\GettingLanguageByCookie',
        'DevGroup\Multilingual\LanguageEvents\GettingLanguageByUserInformation',
        'DevGroup\Multilingual\LanguageEvents\GettingLanguageByGeo',
        'DevGroup\Multilingual\LanguageEvents\GettingLanguageByUrl',
    ];

    public $modelsMap = [
        'Language' => 'DevGroup\Multilingual\models\Language',
        'CountryLanguage' => 'DevGroup\Multilingual\models\CountryLanguage',
        'City' => 'DevGroup\Multilingual\models\City'
    ];


    /**
     * @var array languages
     */
    protected $_languages = [];

    protected $_preferred_city = null;

    protected $_preferred_country = null;

    /**
     * Initializes the component
     */
    public function init()
    {
        parent::init();
        $this->language_id = $this->default_language_id;
    }

    public function getAllLanguages()
    {
        if ($this->contextId !== null) {
            /** @var Context $context */
            $context = Context::findOne($this->contextId);
            if ($context !== null) {
                return $context->languages;
            }
        }
        if ($this->_languages === []) {
            if (is_subclass_of($this->modelsMap['Language'], LanguageInterface::class)) {
                $this->_languages = array_reduce(
                    call_user_func([$this->modelsMap['Language'], 'getAll']),
                    function ($arr, $i) {
                        $arr[$i->id] = $i;
                        return $arr;
                    },
                    []
                );
            }
        }
        return $this->_languages;
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
        });
        $app->on(Application::EVENT_BEFORE_ACTION, function () {
            $this->retrieveCookieLanguage();
        });
        $this->registerTranslations();
    }

    /**
     * Add custom translations source
     */
    public function registerTranslations()
    {
        Yii::$app->i18n->translations['@vendor/devgroup/yii2-multilingual/*'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'en-US',
            'basePath' => '@vendor/devgroup/yii2-multilingual/src/translations',
            'fileMap' => [
                '@vendor/devgroup/yii2-multilingual/widget' => 'widget.php',
            ],

        ];
    }

    /**
     * Add custom translations method
     */
    public static function t($category, $message, $params = [], $language = null)
    {
        return Yii::t('@vendor/devgroup/yii2-multilingual/' . $category, $message, $params, $language);
    }

    /**
     * @return string IP where user is located
     */
    public function getIp()
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
        if (!$this->iso_639_2t_geo = $this->getLanguageFromGeo()) {
            $this->geo_default_language_forced = true;
        }

    }

    public function getLanguageFromGeo()
    {
        // ok we have at least geo object, try to find language for it
        if ($this->iso_639_2t_geo === null) {
            if ($this->geo instanceof GeoInfo) {
                $country = $this->geo->country;
                $searchOrder = [
                    'iso_3166_1_alpha_2',
                    'iso_3166_1_alpha_3',
                    'name',
                ];
                foreach ($searchOrder as $attribute) {
                    if (isset($country->$attribute) &&
                        is_subclass_of($this->modelsMap['CountryLanguage'], CountryLanguageInterface::class)
                    ) {
                        $model = call_user_func(
                            [
                                $this->modelsMap['CountryLanguage'],
                                'find'
                            ]
                        )->where([$attribute => $country->$attribute])
                            ->one();
                        if ($model !== null) {
                            $this->iso_639_2t_geo = $model->iso_3166_1_alpha_3;
                            break;
                        }
                    }
                }
            }
        }
        return $this->iso_639_2t_geo;
    }


    /**
     * Retrieves language form cookie
     */
    public function retrieveCookieLanguage()
    {
        if (Yii::$app instanceof \yii\web\Application === false) {
            return;
        }
        if (Yii::$app->request->cookies->has('language_id')) {
            $language_id = intval(Yii::$app->request->cookies->get('language_id')->value);
            if (call_user_func(
                    [
                        $this->modelsMap['Language'],
                        'getById'
                    ],
                    $language_id
                ) !== null
            ) {
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


    public function getPreferredCountry()
    {
        if ($this->_preferred_country === null) {
            $preferred_city = $this->getPreferredCity();
            if ($preferred_city !== null) {
                $this->_preferred_country = $preferred_city->country;
            } elseif ($this->geo() !== null && $this->geo()->country !== null) {
                $this->_preferred_country = call_user_func(
                    [
                        $this->modelsMap['CountryLanguage'],
                        'findOne'
                    ],
                    [
                        'iso_3166_1_alpha_2' => $this->geo()->country->iso_3166_1_alpha_2
                    ]
                );
            }
        }
        return $this->_preferred_country;
    }

    /***
     * @return null|CityInterface
     */
    public function getPreferredCity()
    {
        if ($this->_preferred_city === null
            && is_subclass_of($this->modelsMap['City'], CityInterface::class)
            && $this->needsDetectCity === true
        ) {
            $city_id = Yii::$app->request->get('multilingual-city-id', false);
            if ($city_id === false) {
                $city_id = Yii::$app->request->cookies->getValue('city_id', false);
            }
            if ($city_id !== false) {
                $this->_preferred_city = call_user_func(
                    [
                        $this->modelsMap['City'],
                        'getById'
                    ],
                    $city_id
                );
            } else {
                if ($this->geo() == null) {
                    $this->retrieveInfo();
                }
                $geo = $this->geo() !== null ? $this->geo() : new GeoInfo();
                $this->_preferred_city = call_user_func(
                    [
                        $this->modelsMap['City'],
                        'getPreferredCity'
                    ],
                    $geo->city
                );
            }
            if ($this->_preferred_city !== null &&
                (!Yii::$app->request->cookies->has('city_id') ||
                    Yii::$app->request->cookies->get('city_id') !== $this->_preferred_city->getId()) &&
                $this->cityNeedsConfirmation === false
            ) {
                Yii::$app->response->cookies->add(new Cookie([
                    'name' => 'city_id',
                    'value' => $this->_preferred_city->getId(),
                ]));
            }
        }
        if ($this->_preferred_city === null) {
            $this->cityNeedsConfirmation = true;
        }
        return $this->_preferred_city;
    }

    public function getContextId()
    {
        return isset($this->_languages[$this->language_id]) === true
            ? $this->_languages[$this->language_id]->context_id
            : null;
    }
}
