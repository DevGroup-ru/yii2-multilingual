<?php

namespace DevGroup\Multilingual\LanguageEvents;

class GettingLanguageByUserInformation implements GettingLanguage
{
    public static function gettingLanguage(languageEvent $event)
    {
        if ($event->currentLanguageId == false) {
            foreach (\Yii::$app->request->getAcceptableLanguages() as  $acceptableLanguage) {
                $acceptableLanguage = str_replace('_', '-', strtolower($acceptableLanguage));
                foreach ($event->languages as $id_lang => $language) {
                    $normalizedLanguage = str_replace('_', '-', strtolower($language['yii_language']));
                    if ($normalizedLanguage === $acceptableLanguage || // en-us==en-us
                        strpos($acceptableLanguage, $normalizedLanguage . '-') === 0 || // en==en-us
                        strpos($normalizedLanguage, $acceptableLanguage . '-') === 0
                    ) { // en-us==en
                        $event->currentLanguageId = $id_lang;
                        $event->resultClass = self::class;
                        return;
                    }
                }
            }
        }
    }

}