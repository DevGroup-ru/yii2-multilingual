<?php

namespace DevGroup\Multilingual\models;

use Yii;
use \yii2tech\filedb\ActiveRecord;

class CountryLanguage extends ActiveRecord
{

    public function getLanguage()
    {
        return $this->hasOne(Language::className(), ['id' => 'language_id']);
    }
}