<?php
namespace DevGroup\Multilingual\LanguageEvents;

use DevGroup\Multilingual\models\Language;

class GettingLanguageByUrl implements GettingLanguage, AfterGettingLanguage
{

    protected static $redirectFlag = false;

    public static function gettingLanguage(languageEvent $event)
    {
        if ($event->currentLanguageId === false) {
            $path = explode('/', \Yii::$app->request->pathInfo);
            $folder = array_shift($path);
            $languages = $event->languages;
            $domain = $event->domain;
            /** @var bool|Language $languageMatched */
            foreach ($languages as $language) {
                $matchedDomain = $language->domain === $domain;
                if (empty($language->folder)) {
                    $matchedFolder = $matchedDomain;
                } else {
                    $matchedFolder = $language->folder === $folder;
                }
                if ($matchedDomain && $matchedFolder) {
                    $event->currentLanguageId = $language->id;
                    return;
                }
            }
            self::$redirectFlag = true;
        }
    }

    public static function afterGettingLanguage(languageEvent $event)
    {

        if (self::$redirectFlag === true) {
            // no matched language and not in excluded routes - should redirect to user's regional domain with 302
            \Yii::$app->urlManager->forceHostInUrl = true;
            $event->redirectUrl = \Yii::$app->urlManager->createUrl(
                [
                    \Yii::$app->request->pathInfo,
                    'language_id' => $event->multilingual->language_id
                ]
            );
            \Yii::$app->urlManager->forceHostInUrl = false;
        }

    }


}