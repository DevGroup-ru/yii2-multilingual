<?php

namespace DevGroup\Multilingual;

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
    public $lazy = false;

    protected $geo = null;

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
        if ($this->lazy === false) {
            $app->on(Application::EVENT_BEFORE_REQUEST, function () {
                $this->retrieveInfo();
            });
        }
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

    protected function retrieveInfo()
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

    /**
     * @return \yii\caching\Cache
     */
    public function cache()
    {
        return Yii::$app->get($this->cache);
    }

    public function geo()
    {
        if ($this->geo === null) {
            $this->retrieveInfo();
        }
        return $this->geo;
    }

    public function filedb()
    {
        $filedb = Yii::$app->get($this->filedb);
        if ($filedb === null) {
            throw new InvalidConfigException("You must configure filedb component");
        }
        return $filedb;
    }
}