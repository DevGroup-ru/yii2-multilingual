<?php

namespace DevGroup\Multilingual\widgets;

use yii\base\Widget;

class LanguageConfirmation extends Widget
{

    public function run()
    {


        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = \Yii::$app->multilingual;


        $result = "";
        if ($multilingual->needsConfirmation) {
            $result = $this->render(
                'confirmation-language',
                [
                    'languages' => $multilingual->getAllLanguages(),
                    'currentLanguageId' => $multilingual->language_id,
                    'multilingual' => $multilingual,
                ]
            );
        }
        return $result;
    }
}