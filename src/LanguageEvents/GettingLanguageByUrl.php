<?php

namespace DevGroup\Multilingual\LanguageEvents;

use DevGroup\Multilingual\models\Context;
use DevGroup\Multilingual\models\Language;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

class GettingLanguageByUrl implements GettingLanguage, AfterGettingLanguage
{
    public static function gettingLanguage(LanguageEvent $event)
    {
        if ($event->currentLanguageId === false) {
            $path = explode('/', \Yii::$app->request->pathInfo);
            $folder = array_shift($path);
            $languages = $event->languages;
            $domain = $event->domain;
            $contextExists = false;
            $contexts = call_user_func([$event->multilingual->modelsMap['Context'], 'find'])->all();
            foreach ($contexts as $context) {
                /** @var Context $context */
                if ($context->domain === $domain) {
                    $event->multilingual->context_id = $context->id;
                    $contextExists = true;
                }
            }
            /** @var bool|Language $languageMatched */
            $domainExists = false;
            foreach ($languages as $language) {
                if (true === ($matchedDomain = $language->domain === $domain)) {
                    $domainExists = true;
                }
                if (empty($language->folder)) {
                    $matchedFolder = $matchedDomain;
                } else {
                    $matchedFolder = $language->folder === $folder;
                }
                if ($matchedDomain && $matchedFolder) {
                    $event->currentLanguageId = $language->id;
                    $event->multilingual->context_id = $language->context_id;
                    if (!empty($language->folder) && $language->folder === $event->request->pathInfo) {
                        $event->needRedirect = true;
                    }
                    $event->resultClass = self::class;
                    return;
                }
            }
            if (false === $domainExists && false === $contextExists) {
                throw new NotFoundHttpException();
            }
            $event->needRedirect = true;
        }
    }

    public static function afterGettingLanguage(LanguageEvent $event)
    {
        $languageMatched = $event->languages[$event->multilingual->language_id];
        if ($event->needRedirect === true && $languageMatched->folder) {
            if ($languageMatched->folder === $event->request->pathInfo) {
                $event->redirectUrl = '/' . $event->request->pathInfo . '/';
                $event->redirectCode = 301;
            } else {
                // no matched language and not in excluded routes - should redirect to user's regional domain with 302
                \Yii::$app->urlManager->forceHostInUrl = true;
                $event->redirectUrl = \Yii::$app->urlManager->createUrl(
                    ArrayHelper::merge(
                        [$event->request->pathInfo],
                        \Yii::$app->request->get(),
                        ['language_id' => $event->multilingual->language_id]
                    )
                );
                \Yii::$app->urlManager->forceHostInUrl = false;
                $event->redirectCode = 302;
            }
        }
        if (!empty($languageMatched->domain) && $languageMatched->domain !== $event->domain) {
            // no matched language and not in excluded routes - should redirect to user's regional domain with 302
            \Yii::$app->urlManager->forceHostInUrl = true;
            $event->redirectUrl = $event->sender->createUrl(
                ArrayHelper::merge(
                    [$event->request->pathInfo],
                    \Yii::$app->request->get(),
                    ['language_id' => $event->multilingual->language_id]
                )
            );
            $event->redirectCode = 302;
        }
    }
}
