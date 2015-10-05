<?php

namespace DevGroup\Multilingual\widgets;

use kartik\icons\FlagIconAsset;
use DevGroup\Multilingual\models\Language;
use Yii;
use yii\base\Widget;
use yii\bootstrap\Tabs;

class MultilingualFormTabs extends Widget
{
    /** @var \yii\db\ActiveRecord|\DevGroup\Multilingual\traits\MultilingualTrait|\DevGroup\Multilingual\behaviors\MultilingualActiveRecord */
    public $model = null;

    /** @var string Child view filename */
    public $childView = '_edit';

    /** @var \yii\widgets\ActiveForm */
    public $form = null;

    /** @var array Tabs options */
    public $options = [];

    /**
     * @inheritdoc
     */
    public function run()
    {
        $items = [];

        $languages = Language::find()->all();
        foreach ($languages as $index => $language) {
            $flag = $language->iso_639_1 === 'en' ? 'gb' : $language->iso_639_1;
            $items[] = [
                'label' => '<span class="flag-icon flag-icon-'.$flag.'"></span> '.$language->name,
                'active' => $index === 0,
                'content' => $this->renderFile(
                    $this->childView,
                    [
                        'model' => $this->model->getTranslation($language->id),
                        'form' => $this->form,
                        'language' => $language,
                        'language_id' => $language->id,
                        'attributePrefix' => "[{$language->id}]",
                    ]
                )
            ];

        }
        FlagIconAsset::register($this->view);
        return '<div class="nav-tabs-custom">'.Tabs::widget([
            'items' => $items,
            'options' => $this->options,
            'encodeLabels' => false,
        ]).'</div>';
    }
}