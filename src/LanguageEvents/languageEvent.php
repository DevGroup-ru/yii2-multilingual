<?php

namespace DevGroup\Multilingual\LanguageEvents;

use DevGroup\Multilingual\Multilingual;
use yii\base\Event;

class languageEvent extends Event
{
    public $needRedirect = false;
    public $redirectUrl = false;
    public $redirectCode = 301;
    public $domain;
    public $request;
    public $currentLanguageId = false;
    /** @var Multilingual */
    public $multilingual;
    public $languages = [];
    public $resultClass = null;
}