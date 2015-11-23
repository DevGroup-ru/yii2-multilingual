<?php
namespace DevGroup\Multilingual\LanguageEvents;

class GettingLanguageByGeo implements GettingLanguage
{
    public static function gettingLanguage(languageEvent $event)
    {
        if ($event->currentLanguageId === false && $event->multilingual->geo_default_language_forced === false) {
            $event->currentLanguageId = $event->multilingual->language_id_geo;
        }
    }

}