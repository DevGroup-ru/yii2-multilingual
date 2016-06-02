<?php

namespace DevGroup\Multilingual\models;

class DummyTree implements TreeInterface
{
    /**
     * Get roots as array where key is an id and value is a name of root.
     * Result example:
     * [
     *      '1' => 'Web',
     *      '967' => 'Landing',
     *      '533' => 'Subdomain'
     * ]
     * @return string[]
     */
    public static function getTreeRootsList()
    {
        return [
            '1' => 'Web',
        ];
    }

}
