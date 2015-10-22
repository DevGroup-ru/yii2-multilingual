Yii2 multilingual component
===========================
Allows building yii2 apps for multiple languages using regional URL's and domains

[![Build Status](https://travis-ci.org/DevGroup-ru/yii2-multilingual.svg?branch=master)](https://travis-ci.org/DevGroup-ru/yii2-multilingual)
[![codecov.io](http://codecov.io/github/DevGroup-ru/yii2-multilingual/coverage.svg?branch=master)](http://codecov.io/github/DevGroup-ru/yii2-multilingual?branch=master)

Quick start:
- [Demo Application](https://github.com/DevGroup-ru/yii2-multilingual-demo)
- [GEO detection daemon](https://github.com/DevGroup-ru/sypex-geo-daemon) and [related multilingual provider](https://github.com/DevGroup-ru/yii2-multilingual-sypex-geo-daemon).

**WARNING:** This extension is under active development. 

For support - join [DotPlant2 gitter channel](https://gitter.im/DevGroup-ru/dotplant2).

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

### Configure your application

In your `web.php` config add the following components:

``` php
        // URL Manager is needed to build correct URL's
        'urlManager' => [
            'class' => \DevGroup\Multilingual\components\UrlManager::className(),
            'excludeRoutes' => [
                //'newsletter/index',
                //'newsletter/test',
            ],
            'rules' => [
                '' => 'post/index',
            ],
        ],
        // this is the main language and geo detection component
        'multilingual' => [
            'class' => \DevGroup\Multilingual\Multilingual::className(),
            'default_language_id' => 1,
            // the list of handlers that will try to detect information(see also sypex-geo-daemon provider)
            'handlers' => [
                [
                    'class' => \DevGroup\Multilingual\DefaultGeoProvider::className(),
                    'default' => [
                        'country' => [
                            'name' => 'England',
                            'iso' => 'en',
                        ],
                    ],
                ],
            ],
        ],
        // this is simple storage for Languages configuration
        'filedb' => [
            'class' => 'yii2tech\filedb\Connection',
            'path' => __DIR__ . '/data',
        ],
```

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

### HrefLang

Add one line into your HEAD section of layout view:

```php
<?= \DevGroup\Multilingual\widgets\HrefLang::widget() ?>
```

## Tips

1. Remember to take care of language_id when caching multilingual or translatable content
2. In requests to excluded routes there may be no `language_id`, but probably can be `cookie_language_id`
3. If you want to generate URL's from console application you may need to configure additional params(see https://github.com/DevGroup-ru/yii2-multilingual-demo/blob/master/config/console.php)
4. MultilingualTrait adds default conditions to find and is not required for use. But if you don't use it - you must manually configure proper relations.
5. Add indexes to your translation tables, especially for `language_id` and `model_id` pair.

## Credits and inspiration sources

- Michael Härtl - author of codemix/yii2-localeurls
- Company BINOVATOR - authors of SypexGeo database and php class
- Alexander Kochetov (@creocoder) - yii2-translatable package
