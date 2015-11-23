<?php
namespace DevGroup\Multilingual\LanguageEvents;

interface AfterGettingLanguage
{
    public static function afterGettingLanguage(languageEvent $event);
}