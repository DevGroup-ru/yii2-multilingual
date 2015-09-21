<?php

namespace DevGroup\Multilingual\tests\models;

/**
 * Class PostTranslation
 * @property integer $author_id
 */
class PostTranslation extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%post_translation}}';
    }

    public function rules()
    {
        return [
            ['title', 'required'],
            ['body', 'string'],
        ];
    }
}