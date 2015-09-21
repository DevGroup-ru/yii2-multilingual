<?php

namespace DevGroup\Multilingual\models;

use Yii;
use \yii2tech\filedb\ActiveRecord;

/**
 * Class Language
 *
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
class Language extends ActiveRecord
{

}