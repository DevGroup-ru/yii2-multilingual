<?php

namespace DevGroup\Multilingual\tests\models;

/**
 * Class Post
 * @property integer $author_id
 */
class Post extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%post}}';
    }
}