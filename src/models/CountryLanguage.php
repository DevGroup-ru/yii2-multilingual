<?php

namespace DevGroup\Multilingual\models;

use Yii;
use \yii2tech\filedb\ActiveRecord;

/**
 * Class CountryLanguage
 *
 * @property integer $id
 * @property string $name
 * @property string $name_native
 * @property integer $language_id
 * @property string $iso_3166_1_alpha_2
 * @property string $iso_3166_1_alpha_3
 */
class CountryLanguage extends ActiveRecord implements CountryLanguageInterface
{

    public function getLanguage()
    {
        return $this->hasOne(Language::className(), ['id' => 'language_id']);
    }
}