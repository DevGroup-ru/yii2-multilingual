<?php

namespace DevGroup\Multilingual\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\Model;
use yii\base\ModelEvent;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;


class MultilingualActiveRecord extends Behavior
{
    /** @var bool|string Translation model class name */
    public $translationModelClass = false;

    public $translationAttributes = [];

    public $translationRelation = 'translations';
    public $defaultTranslationRelation = 'defaultTranslation';

    /**
     * @var string|bool attribute that stores published state in translation record, false if published state is N/A
     */
    public $translationPublishedAttribute = 'is_published';
    /**
     * @var int|string Value that means translation is published
     */
    public $translationPublishedAttributeValue = 1;

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);

        $className = $this->getTranslationModelClassName();
        /** @var ActiveRecord $exampleModel */
        $exampleModel = new $className;

        $this->translationAttributes = $exampleModel->attributes();
        if ($owner->hasMethod('advancedTranslatableAttributes')) {
            $this->translationAttributes = ArrayHelper::merge(
                $this->translationAttributes,
                $owner->advancedTranslatableAttributes()
            );
        }
    }

    /**
     * @return string Classname of translation model
     */
    public function getTranslationModelClassName()
    {
        if ($this->translationModelClass === false) {
            return $this->owner->className() . 'Translation';
        } else {
            return $this->translationModelClass;
        }
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_VALIDATE => 'afterValidate',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
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

    /**
     * Returns the translation model for the specified language.
     * @param int|null $language_id
     * @return ActiveRecord
     */
    public function getTranslation($language_id = null)
    {
        /** @var ActiveRecord $translation */

        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;
        if ($language_id === null) {
            $language_id = $multilingual->language_id;
        }
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        if ($language_id === $multilingual->language_id && !$owner->isRelationPopulated($this->translationRelation)) {
            $language_id = $multilingual->language_id;
            $translation = $owner->{$this->defaultTranslationRelation};
            if ($translation !== null) {
                return $translation;
            }
        } else {
            // language id specified and it's not default
            $translations = $owner->{$this->translationRelation};
            foreach ($translations as $item) {
                if ($item->language_id === $language_id) {
                    return $item;
                }
            }
        }
        // translation does not exists!

        /** @var ActiveRecord $class */
        $class = $this->getTranslationModelClassName();
        /** @var ActiveRecord $translation */
        $translation = new $class();
        $translation->loadDefaultValues();
        $translation->setAttribute('language_id', $language_id);

        $translations = $this->owner->{$this->translationRelation};
        $translations[] = $translation;

        $owner->populateRelation($this->translationRelation, $translations);


        if ($language_id === $multilingual->language_id) {
            $owner->populateRelation($this->defaultTranslationRelation, $translation);
        }

        return $translation;
    }

    /**
     * Returns a value indicating whether the translation model for the specified language exists.
     * @param string|null $language_id
     * @param bool $checkIsPublished Check if translation is published
     * @return boolean
     */
    public function hasTranslation($language_id = null, $checkIsPublished = true)
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->multilingual;
        if ($language_id === null) {
            $language_id = $multilingual->language_id;
        }
        /** @var ActiveRecord $owner */
        $owner = $this->owner;

        if ($language_id === $multilingual->language_id) {
            // we were asked for default language
            /** @var ActiveRecord $translation */
            $translation = $owner->{$this->defaultTranslationRelation};
            if ($translation === null) {
                return false;
            }

            if ($checkIsPublished === true) {
                return $this->checkIsPublished($translation);
            }
        }

        /* @var ActiveRecord $translation */
        foreach ($owner->{$this->translationRelation} as $translation) {
            if ($translation->getAttribute('language_id') === $language_id) {
                if ($checkIsPublished === true) {
                    return $this->checkIsPublished($translation);
                } else {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Performs a check of publish state for translation record
     * @param ActiveRecord $translationRecord
     * @return bool
     */
    public function checkIsPublished(ActiveRecord $translationRecord)
    {
        if ($this->translationPublishedAttribute === false) {
            return true;
        }
        return
            $translationRecord->getAttribute($this->translationPublishedAttribute)
            == $this->translationPublishedAttributeValue;
    }

    /**
     * @return void
     */
    public function afterValidate()
    {
        if (!Model::validateMultiple($this->owner->{$this->translationRelation})) {
            /** @var ActiveRecord $owner */
            $owner = $this->owner;
            $owner->addError($this->translationRelation);
        }
    }

    /**
     * @return void
     */
    public function afterSave()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        /* @var ActiveRecord $translation */
        $translations = $owner->{$this->translationRelation};
        // as we link all current related models - they will duplicate in related
        // that's because of "update lazily loaded related objects" in link
        // so we are saving them into variable and empty _related of model
        $owner->populateRelation($this->translationRelation, []);

        foreach ($translations as $translation) {

            $translation->loadDefaultValues();
            $owner->link($this->translationRelation, $translation);
        }
        // now all translations saved and are in _related !

    }

    /**
     * @return boolean
     */
    public function beforeDelete(ModelEvent $event)
    {
        $result = $event->isValid;
        if ($result !== false) {
            $translations = $this->owner->{$this->translationRelation};
            /* @var ActiveRecord $translation */
            foreach ($translations as $translation) {
                $translation->delete();
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return in_array($name, $this->translationAttributes) ?: parent::canGetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return in_array($name, $this->translationAttributes) ?: parent::canSetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        return $this->getTranslation()->{$name};
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        $translation = $this->getTranslation();
        $translation->{$name} = $value;
    }
}