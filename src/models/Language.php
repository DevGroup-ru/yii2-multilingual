<?php

namespace DevGroup\Multilingual\models;

use Yii;
use \yii2tech\filedb\ActiveRecord;

/**
 * Class Language
 *
 * @property integer $id
 * @property string $name
 * @property string $name_native
 * @property string $iso_639_1 ISO 639-1
 * @property string $iso_639_2t ISO 639-2/T
 * @property string $domain
 * @property string $folder
 * @property string $yii_language
 * @property string $hreflang
 * @property string $db_table_postfix
 */
class Language extends ActiveRecord implements LanguageInterface
{

    protected static $_all = [];

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

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }
}