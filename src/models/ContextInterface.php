<?php

namespace DevGroup\Multilingual\models;

interface ContextInterface
{
    /**
     * Get all contexts as list of data
     * @return array in the next format:
     *  [
     *      null => 'Common context',
     *      '1' => 'The first context',
     *      // ...
     *  ]
     */
    public static function getListData();
}
