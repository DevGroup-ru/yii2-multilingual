<?php

return [
    1 => [
        'id' => 1,
        'name' => 'English',
        'name_native' => 'English',
        'iso_639_1' => 'en',
        'iso_639_2t' => 'eng',
        'hreflang' => 'en',
        'context_rules' => [
            1 => [
                'domain' => 'example.com',
                'folder' => 'en',
            ],
            2 => [
                'domain' => 'en.example.org',
                'folder' => '',
            ],
            3 => [
                'domain' => 'en.example.net',
                'folder' => '',
            ]
        ],

        'yii_language' => 'en-US',
        'db_table_postfix' => 'en',
        'sort_order' => '1',
    ],
    2 => [
        'id' => 2,
        'name' => 'Russian',
        'name_native' => 'Русский',
        'iso_639_1' => 'ru',
        'iso_639_2t' => 'rus',
        'hreflang' => 'ru',
        'context_rules' => [
            1 => [
                'domain' => 'example.ru',
                'folder' => '',
            ],
            2 => [
                'domain' => 'ru.example.org',
                'folder' => '',
            ],
            3 => [
                'domain' => 'example.net',
                'folder' => '',
            ],
        ],
        'yii_language' => 'ru',
        'db_table_postfix' => 'ru',
        'sort_order' => '2',
    ],
    3 => [
        'id' => 3,
        'name' => 'German',
        'name_native' => 'Deutsch',
        'iso_639_1' => 'de',
        'iso_639_2t' => 'deu',
        'hreflang' => 'de',
        'context_rules' => [
            1 => [
                'domain' => 'example.com',
                'folder' => 'de',
            ]
        ],
        'yii_language' => 'de',
        'db_table_postfix' => 'de',
        'sort_order' => '3',
    ],


];
