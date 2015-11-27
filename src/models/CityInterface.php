<?php
namespace DevGroup\Multilingual\models;

interface CityInterface
{
    /**
     * get preferred city by Geo data
     * @param \DevGroup\Multilingual\City $city
     * @return null|CityInterface
     */
    public static function getPreferredCity(\DevGroup\Multilingual\City $city);

    /**
     * get city by id
     * @param $id
     * @return null|CityInterface
     */
    public static function getById($id);

    /**
     * get all city
     * @return array of objects
     */
    public static function getAll();

    /**
     * name of city
     * @return string
     */
    public function getName();

    /**
     * id of city
     * @return int
     */
    public function getId();


}