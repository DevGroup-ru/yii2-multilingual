<?php

namespace DevGroup\Multilingual\widgets;

use yii\base\Widget;

class CityConfirmation extends Widget
{

    public $viewFile = 'city-confirmation';

    public function run()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = \Yii::$app->multilingual;
        $city = $multilingual->getPreferredCity();
        $country = $multilingual->getPreferredCountry();

        return $result = $this->render(
            $this->viewFile,
            [
                'multilingual' => $multilingual,
                'city' => $city,
                'country' => $country,
                'allCites' => call_user_func(
                    [
                        $multilingual->modelsMap['City'],
                        'getAll'
                    ]
                )
            ]
        );

    }

}