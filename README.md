Yii2 multilingual component
===========================
Allows building yii2 apps for multiple languages using regional URL's and domains

[![Build Status](https://travis-ci.org/DevGroup-ru/yii2-multilingual.svg?branch=master)](https://travis-ci.org/DevGroup-ru/yii2-multilingual)
[![codecov.io](http://codecov.io/github/DevGroup-ru/yii2-multilingual/coverage.svg?branch=master)](http://codecov.io/github/DevGroup-ru/yii2-multilingual?branch=master)

**WARNING:** This extension is under active development. Don't use it in production!

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist devgroup/yii2-multilingual "*"
```

or add

```
"devgroup/yii2-multilingual": "*"
```

to the require section of your `composer.json` file.


## Usage

### Creating translatable ActiveRecord

As our implementation is based on `creocoder/yii2-translatable` - the use creation of multilingual ActiveRecords is very similar.

The main differences from `creocoder2/yii2translatable`:
- no need to set `translationAttributes` - they automatically detected from translation model
- language field is `language_id` of integer type


In your ActiveRecord class(for example `Post`) add trait and behavior:

``` php
use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\traits\MultilingualTrait;

/**
 * Class Post
 * @property integer $author_id
 */
class Post extends \yii\db\ActiveRecord
{
    use MultilingualTrait;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'multilingual' => [
                'class' => MultilingualActiveRecord::className(),
                'translationPublishedAttribute' => 'is_active',
            ],
        ];
    }


    public static function tableName()
    {
        return '{{%post}}';
    }
}
```

## Credits and inspiration sources

- Michael Härtl - author of codemix/yii2-localeurls
- Company BINOVATOR - authors of SypexGeo database and php class
- Alexander Kochetov (@creocoder) - yii2-translatable package
