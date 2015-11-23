<?php

namespace DevGroup\Multilingual\languageEvents;

use yii\base\Event;

class languageEvent extends Event
{
    public $redirectUrl = false;
    public $domain;
    public $currentLanguageId = false;
    public $multilingual = false;
    public $languages = [];
}