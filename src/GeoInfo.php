<?php

namespace DevGroup\Multilingual;

use Yii;

class GeoInfo
{
    /** @var null|Country */
    public $country = null;
    /** @var null|City */
    public $city = null;
    /** @var null|Region */
    public $region = null;

    /** @var string User's IP */
    public $ip = null;

    public function __construct($config=[])
    {
        $this->country = Yii::configure(new Country(), isset($config['country']) ? $config['country'] : []);
        $this->city    = Yii::configure(new City(),    isset($config['city'])    ? $config['city']    : []);
        $this->region  = Yii::configure(new Region(),  isset($config['region'])  ? $config['region']  : []);

    }
}