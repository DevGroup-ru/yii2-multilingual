<?php

namespace DevGroup\Multilingual\tests\models;

use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\traits\MultilingualTrait;

/**
 * Class AllPost
 * @property integer $author_id
 */
class AllPost extends \yii\db\ActiveRecord
{
    use MultilingualTrait;

    public function behaviors()
    {
        return [
            'multilingual' => [
                'class' => MultilingualActiveRecord::className(),
                'translationModelClass' => PostTranslation::className(),
                'translationPublishedAttribute' => false,
            ],
        ];
    }


    public static function tableName()
    {
        return '{{%post}}';
    }


}