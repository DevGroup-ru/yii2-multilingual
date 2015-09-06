<?php

namespace DevGroup\Multilingual;

use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;

class Multilingual extends Component implements BootstrapInterface
{
    public $useXForwardedFor = false;
    public $useClientIp = false;

    public $mockIp = false;
    public $lazy = false;

    protected $geo = null;

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
        if ($this->mockIp !== null) {
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
        foreach ($this->handlers as $handler) {
            /** @var GeoProviderInterface $object */
            $object = Yii::createObject($handler);
            $info = $object->getGeoInfo($ip);
            if ($info instanceof GeoProviderInterface) {
                $info->ip = $ip;
                $this->geo = $info;
                return;
            }
        }
    }

    public function geo()
    {
        if ($this->geo === null) {
            $this->retrieveInfo();
        }
        return $this->geo;
    }
}