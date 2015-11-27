<?php

namespace DevGroup\Multilingual\widgets;

use yii\base\Widget;

class CityConfirmation extends Widget
{

    public function run()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = \Yii::$app->multilingual;
        $city = $multilingual->getPreferredCity();
        $result = "";
        if ($multilingual->cityNeedsConfirmation) {
            $result = $this->render(
                'city-confirmation',
                [
                    'city' => $city,
                    'allCites' => call_user_func(
                        [
                            $multilingual->modelsMap['City'],
                            'getAll'
                        ]
                    )
                ]
            );
        }
        return $result;

    }

}