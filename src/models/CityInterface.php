<?php
namespace DevGroup\Multilingual\models;

use DevGroup\Multilingual\City;

interface CityInterface
{
    public static function getPreferredCity(City $city);

    public static function getById($id);

    public static function getAll();

    public function getName();

    public function getId();


}