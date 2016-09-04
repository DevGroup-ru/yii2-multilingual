<?php

namespace DevGroup\Multilingual\traits;

use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

trait MultilingualTrait
{
    protected static $translationTableName;

    /**
     * @return string
     */
    public static function getTranslationTableName()
    {
        if (self::$translationTableName === null) {
            /** @var ActiveRecord|MultilingualActiveRecord $model */
            $model = new static;
            /** @var ActiveRecord $translationModelClassName */
            $translationModelClassName = $model->getTranslationModelClassName();
            self::$translationTableName = $translationModelClassName::tableName();
        }
        return self::$translationTableName;
    }

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

        if (method_exists(get_called_class(), 'applyDefaultScope')) {
            $query = call_user_func([get_called_class(), 'applyDefaultScope'], $query);
        } else {
            /** @var ActiveRecord|MultilingualActiveRecord $modelInstance */
            $modelInstance = new self;
            if ($modelInstance->translationPublishedAttribute !== false) {
                /** @var ActiveRecord $translationModelClassName */
                $translationModelClassName = $modelInstance->getTranslationModelClassName();
                self::$translationTableName = $translationModelClassName::tableName();
                // add condition on
                $where = [
                    self::$translationTableName . '.' . $modelInstance->translationPublishedAttribute =>
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
            ->where([static::getTranslationTableName() . '.language_id' => Yii::$app->multilingual->language_id]);
    }

    /**
     * @return ActiveQuery
     */
    public function getTranslations()
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\Multilingual\behaviors\MultilingualActiveRecord $this */
        return $this->hasMany($this->getTranslationModelClassName(), ['model_id' => 'id']);
    }

}
