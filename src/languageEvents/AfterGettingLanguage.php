<?php
namespace DevGroup\Multilingual\languageEvents;

interface AfterGettingLanguage
{
    public static function afterGettingLanguage(languageEvent $event);
}