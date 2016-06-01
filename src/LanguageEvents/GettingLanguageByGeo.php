<?php

namespace DevGroup\Multilingual\LanguageEvents;

use DevGroup\Multilingual\models\Language;

class GettingLanguageByGeo implements GettingLanguage
{
    public static function gettingLanguage(LanguageEvent $event)
    {
        // ok we have at least geo object, try to find language for it
        if ($event->currentLanguageId === false && $event->multilingual->geo_default_language_forced === false) {
            $language = call_user_func([$event->multilingual->modelsMap['Language'], 'find'])
                ->where(
                    [
                        'context_id' => $event->multilingual->context_id,
                        'iso_639_2t' => $event->multilingual->iso_639_2t_geo,
                    ]
                )
                ->one();
            if ($language !== null) {
                $event->currentLanguageId = $language->id;
                $event->resultClass = self::class;
            }
        }
    }
}
