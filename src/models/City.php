<?php
namespace DevGroup\Multilingual\models;

use DevGroup\Multilingual\helper\GeoHelper;
use yii2tech\filedb\ActiveRecord;

/***
 * Class City
 * @package DevGroup\Multilingual\models
 * @property integer $id
 * @property string $iso;
 * @property string $name;
 * @property float $lat;
 * @property float $lon;
 */
class City extends ActiveRecord implements CityInterface
{
    /**
     * @var CityInterface[]
     */
    protected static $_all = [];

    public static function getPreferredCity(\DevGroup\Multilingual\City $city)
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = \Yii::$app->multilingual;
        $model = null;
        if ($city->name !== null) {
            $model = self::findOne([
                'name' => $city->name
            ]);
            if (!$model) {
                $multilingual->cityNeedsConfirmation = true;
                $dist = false;
                foreach (self::find()->all() as $item) {
                    /**@var $item CityInterface */
                    $newDist = GeoHelper::getDistance($item->lat, $item->lon, $city->lat, $city->lon);
                    if (($dist === false || $newDist < $dist) &&
                        ($multilingual->cityMaxDistance == false || $newDist < $multilingual->cityMaxDistance)
                    ) {
                        $dist = $newDist;
                        $model = $item;
                    }
                }
            }
        }
        return $model;
    }


    public static function getById($id)
    {
        return self::findOne(['id' => $id]);
    }


    public static function getAll()
    {
        if (static::$_all === []) {
            foreach (self::find()->all() as $item) {
                static::$_all[$item->id] = $item;
            }
        }
        return static::$_all;
    }


    public function getName()
    {
        return $this->name;
    }


    public function getId()
    {
        return $this->id;
    }
}