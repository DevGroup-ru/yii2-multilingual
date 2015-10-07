<?php

namespace DevGroup\Multilingual\widgets;

use DevGroup\Multilingual\models\Language;
use Yii;
use yii\base\Widget;

class LanguageSelector extends Widget
{
    public $viewFile = 'language-selector';

    public $blockClass = 'b-language-selector';
    public $blockId = '';

    public function run()
    {
        $languages = Language::find()
            ->indexBy('id')
            ->all();
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->get('multilingual');
        $currentLanguageId = $multilingual->language_id;

        if (empty($this->blockId)) {
            $this->blockId = 'language-selector-'.$this->getId();
        }

        return $this->render(
            $this->viewFile,
            [
                'languages' => $languages,
                'currentLanguageId' => $currentLanguageId,
                'multilingual' => $multilingual,

                'blockId' => $this->blockId,
                'blockClass' => $this->blockClass,
            ]
        );
    }
}