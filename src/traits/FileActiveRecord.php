<?php

namespace DevGroup\Multilingual\traits;

use yii2tech\filedb\ActiveRecord;

trait FileActiveRecord
{
    /**
     * Init events of this trait.
     */
    public function initFileActiveRecord()
    {
        if (count(static::primaryKey()) === 1) {
            $this->on(self::EVENT_BEFORE_INSERT, function ($event) {
                if (empty($event->sender->{$event->data['pkName']})) {
                    /** @var ActiveRecord $className */
                    $className = $event->data['className'];
                    $lastModel = $className::find()->orderBy([$event->data['pkName'] => SORT_DESC])->one();
                    $event->sender->{$event->data['pkName']} = $lastModel !== null ? $lastModel->{$event->data['pkName']} + 1 : 1;
                }
            }, ['pkName' => static::primaryKey()[0], 'className' => static::class]);
        }
    }

    /**
     * Initializes the object.
     * This method is called at the end of the constructor.
     * The default implementation will trigger an [[EVENT_INIT]] event.
     * If you override this method, make sure you call the parent implementation at the end
     * to ensure triggering of the event.
     */
    public function init()
    {
        parent::init();
        $this->initFileActiveRecord();
    }
}
