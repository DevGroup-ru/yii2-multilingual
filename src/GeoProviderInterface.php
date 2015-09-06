<?php

namespace DevGroup\Multilingual;


interface GeoProviderInterface
{
    /**
     * @var string $ip
     * @return GeoInfo|null
     */
    public function getGeoInfo($ip);
}