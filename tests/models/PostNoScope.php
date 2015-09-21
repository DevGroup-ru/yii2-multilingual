<?php

namespace DevGroup\Multilingual\tests\models;

use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\traits\MultilingualTrait;

/**
 * Class PostNoScope
 * @property integer $author_id
 */
class PostNoScope extends \yii\db\ActiveRecord
{
    use MultilingualTrait;

    public function behaviors()
    {
        return [
            'multilingual' => [
                'class' => MultilingualActiveRecord::className(),
                'translationModelClass' => PostTranslation::className(),
            ],
        ];
    }


    public static function tableName()
    {
        return '{{%post}}';
    }


}