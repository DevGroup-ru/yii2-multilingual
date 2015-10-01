<?php

namespace DevGroup\Multilingual\traits;

use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

trait MultilingualTrait
{
    /**
     * @return ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public static function find()
    {
        /** @var ActiveQuery $query */
        $query = Yii::createObject(ActiveQuery::className(), [get_called_class()]);
        $query = $query
            ->innerJoinWith(['defaultTranslation']);
        /** @var ActiveRecord|MultilingualActiveRecord $modelInstance */

        if (method_exists(get_called_class(), 'applyDefaultScope')) {
            $query = call_user_func([get_called_class(), 'applyDefaultScope'], $query);
        } else {
            $modelInstance = new self;
            if ($modelInstance->translationPublishedAttribute !== false) {
                /** @var ActiveRecord $translationModelClassName */
                $translationModelClassName = $modelInstance->getTranslationModelClassName();

                $tableName = $translationModelClassName::tableName();

                // add condition on
                $where = [
                    "{$tableName}.{$modelInstance->translationPublishedAttribute}" =>
                        $modelInstance->translationPublishedAttributeValue
                ];

                unset($modelInstance);

                $query = $query->where($where);
            }
        }


        return $query;
    }

    /**
     * @return ActiveQuery
     */
    public function getDefaultTranslation()
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\Multilingual\behaviors\MultilingualActiveRecord $this */
        return $this->hasOne($this->getTranslationModelClassName(), ['model_id' => 'id'])
            ->where(['language_id' => Yii::$app->multilingual->language_id]);
    }

    /**
     * @return ActiveQuery
     */
    public function getTranslations()
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\Multilingual\behaviors\MultilingualActiveRecord $this */
        return $this->hasMany($this->getTranslationModelClassName(), ['model_id' => 'id']);
    }

    /**
     * Returns the translation model for the specified language.
     * @param int|null $language_id
     * @return ActiveRecord
     */
    public function translate($language_id = null)
    {
        return $this->getTranslation($language_id);
    }
}