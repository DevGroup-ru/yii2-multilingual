<?php
namespace DevGroup\Multilingual\models;

interface CityInterface
{
    public static function getPreferredCity(\DevGroup\Multilingual\City $city);

    public static function getById($id);

    public static function getAll();

    public function getName();

    public function getId();


}