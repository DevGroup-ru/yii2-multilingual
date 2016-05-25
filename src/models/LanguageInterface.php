<?php

namespace DevGroup\Multilingual\models;

interface LanguageInterface
{
    public static function getById($id);

    public static function getAll();

    public function getId();

    public function getName();
}
