<?php

namespace DevGroup\Multilingual\LanguageEvents;

use DevGroup\Multilingual\models\Context;

class GettingLanguageByUserInformation implements GettingLanguage
{
    public static function gettingLanguage(LanguageEvent $event)
    {
        if ($event->currentLanguageId == false) {
            $contextId = $event->multilingual->context_id;
            $languages = null === $contextId ? $event->languages : Context::findOne($contextId)->languages;
            foreach (\Yii::$app->request->getAcceptableLanguages() as $acceptableLanguage) {

                $acceptableLanguage = str_replace('_', '-', strtolower($acceptableLanguage));
                foreach ($languages as $language) {
                    $normalizedLanguage = str_replace('_', '-', strtolower($language['yii_language']));
                    if ($normalizedLanguage === $acceptableLanguage || // en-us==en-us
                        strpos($acceptableLanguage, $normalizedLanguage . '-') === 0 || // en==en-us
                        strpos($normalizedLanguage, $acceptableLanguage . '-') === 0
                    ) { // en-us==en
                        $event->currentLanguageId = $language->id;
                        $event->resultClass = self::class;

                        return;
                    }
                }
            }
        }
    }
}
