<?php

namespace DevGroup\Multilingual\LanguageEvents;

class GettingLanguageByUserInformation implements GettingLanguage
{
    public static function gettingLanguage(languageEvent $event)
    {
        if ($event->currentLanguageId == false) {
            $languages = array_reduce(
                $event->languages,
                function ($arr, $i) {
                    $arr[$i['yii_language']] = $i['id'];
                    return $arr;
                },
                []
            );
            if ($lang_name = \Yii::$app->request->getPreferredLanguage(array_keys($languages))) {
                $event->currentLanguageId = $languages[$lang_name];
            }
        }
    }

}