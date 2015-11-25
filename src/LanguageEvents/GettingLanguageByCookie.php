<?php
namespace DevGroup\Multilingual\LanguageEvents;

use yii\web\Cookie;

class GettingLanguageByCookie implements GettingLanguage, AfterGettingLanguage
{
    public static function gettingLanguage(languageEvent $event)
    {

        if ($event->currentLanguageId === false) {
            if (\Yii::$app->request->cookies->has('language_id') &&
                in_array(
                    \Yii::$app->request->cookies->get('language_id')->value,
                    array_keys($event->languages)
                )
            ) {
                $event->currentLanguageId = \Yii::$app->request->cookies->get('language_id')->value;
                $event->multilingual->cookie_language_id = $event->currentLanguageId;
                $event->resultClass = self::class;
            }
        }
    }

    public static function afterGettingLanguage(languageEvent $event)
    {

        if (\Yii::$app->request->cookies->getValue('language_id') !== $event->currentLanguageId) {
            \Yii::$app->response->cookies->add(new Cookie([
                'name' => 'language_id',
                'value' => $event->currentLanguageId,
            ]));
        }

    }
}