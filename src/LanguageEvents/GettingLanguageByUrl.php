<?php

namespace DevGroup\Multilingual\LanguageEvents;

use DevGroup\Multilingual\models\Context;
use DevGroup\Multilingual\models\Language;
use Intervention\Image\Exception\NotFoundException;
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
                    $event->multilingual->context_id = (int) $context->id;
                    $contextExists = true;
                }
            }

            /** @var bool|Language $languageMatched */
            $domainExists = false || $contextExists;
            foreach ($languages as $language) {
                foreach ($language->context_rules as $context_id => $rule) {
                    $context_id = (int) $context_id;

                    if (true === ($matchedDomain = $rule['domain'] === $domain)) {
                        $domainExists = true;
                    }
                    if (empty($rule['folder'])) {
                        $matchedFolder = $matchedDomain;
                    } else {
                        $matchedFolder = $rule['folder'] === $folder;
                    }
                    if ($matchedDomain && $matchedFolder) {
                        // we have matched language structure, but not the same context as above - that's weird situation
                        if ($contextExists && $event->multilingual->context_id !== $context_id) {
                            throw new NotFoundException();
                        }
                        $event->currentLanguageId = $language->id;
                        $event->multilingual->context_id = $context_id;
                        if (!empty($rule['folder']) && $rule['folder']=== $event->request->pathInfo) {
                            $event->needRedirect = true;
                        }
                        $event->resultClass = self::class;

                        return;
                    }
                }
//                if (true === ($matchedDomain = $language->domain === $domain)) {
//                    $domainExists = true;
//                }
//                if (empty($language->folder)) {
//                    $matchedFolder = $matchedDomain;
//                } else {
//                    $matchedFolder = $language->folder === $folder;
//                }
//
//                if ($matchedDomain && $matchedFolder) {
//                    $event->currentLanguageId = $language->id;
//                    $event->multilingual->context_id = $language->context_id;
//                    if (!empty($language->folder) && $language->folder === $event->request->pathInfo) {
//                        $event->needRedirect = true;
//                    }
//                    $event->resultClass = self::class;
//
//                    return;
//                }
            }

            if (false === $domainExists && false === $contextExists) {
                throw new NotFoundHttpException();
            }
            $event->needRedirect = true;
        }

    }

    public static function afterGettingLanguage(LanguageEvent $event)
    {
        /** @var Language $languageMatched */
        $languageMatched = $event->languages[$event->multilingual->language_id];
        $rules = $languageMatched->rulesForContext($event->multilingual->context_id);
        $hasRedirectTarget = $rules !== null && $rules['folder'];
        if ($event->needRedirect === true && $hasRedirectTarget) {
            if ($rules['folder'] === $event->request->pathInfo) {

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
        if (!empty($rules['domain']) && $rules['domain'] !== $event->domain) {
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
