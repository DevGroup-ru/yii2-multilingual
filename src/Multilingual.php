<?php

namespace DevGroup\Multilingual;

use DevGroup\Multilingual\models\CountryLanguage;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\base\InvalidConfigException;

class Multilingual extends Component implements BootstrapInterface
{
    public $useXForwardedFor = false;
    public $useClientIp = false;

    public $mockIp = false;

    /** @var GeoInfo */
    protected $geo = null;

    /** @var null|integer */
    public $language_id_geo = null;

    public $language_id = null;

    public $geo_default_language_forced = false;

    public $default_language_id = null;

    public $cache = 'cache';
    /**
     * @var int Cache lifetime in seconds. Defaults to 2 weeks(1209600).
     */
    public $cacheLifetime = 1209600;

    public $filedb = 'filedb';

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

    }

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
            if ($info instanceof GeoInfo && ($info->country->iso_3166_1_alpha_2 || $info->country->iso_3166_1_alpha_3 || $info->country->name)) {
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

    public function retrieveLanguageFromGeo()
    {
        // ok we have at least geo object, try to find language for it
        $model = null;
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

}