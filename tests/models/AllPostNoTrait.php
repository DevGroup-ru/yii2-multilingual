<?php

namespace DevGroup\Multilingual\tests\models;

use Yii;
use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\traits\MultilingualTrait;

/**
 * Class AllPostNoTrait
 * @property integer $author_id
 */
class AllPostNoTrait extends \yii\db\ActiveRecord
{

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

    public function getDefaultTranslation()
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\Multilingual\behaviors\MultilingualActiveRecord $this */
        return $this->hasOne($this->getTranslationModelClassName(), ['model_id' => 'id'])
            ->where(['language_id' => Yii::$app->multilingual->language_id]);
    }

    public function getTranslations()
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\Multilingual\behaviors\MultilingualActiveRecord $this */
        return $this->hasMany($this->getTranslationModelClassName(), ['model_id' => 'id']);
    }
}