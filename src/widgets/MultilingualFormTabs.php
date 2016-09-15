<?php

namespace DevGroup\Multilingual\widgets;

use DevGroup\Multilingual\models\Context;
use kartik\icons\FlagIconAsset;
use DevGroup\Multilingual\models\Language;
use Yii;
use yii\base\InvalidParamException;
use yii\base\Widget;
use yii\bootstrap\Tabs;
use yii\helpers\ArrayHelper;

/**
 * MultilingualFormTabs is a special Widget for rendering form inputs based on model language
 *
 * @package DevGroup\Multilingual\widgets
 */
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

    /** @var array Additional tabs for list */
    public $additionalTabs = [];

    /** @var string Additional class for base nav-tabs-custom tag */
    public $tagClass = '';

    /** @var string HTML for footer of this nav-tabs-custom */
    public $footer = '';

    /** @var int the context id. It is used to get languages. By default we use current context */
    public $contextId;

    /**
     * @inheritdoc
     */
    public function run()
    {
        $items = [];

        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->get('multilingual');
        if  ($this->contextId !== null) {
            $context = Context::findOne($this->contextId);
            if ($context === null) {
                throw new InvalidParamException('Context not found');
            }
            $languages = $context->languages;
        } else {
            $languages = $multilingual->getAllLanguages();
        }
        foreach ($languages as $index => $language) {
            $flag = $language->iso_639_1 === 'en' ? 'gb' : $language->iso_639_1;
            $items[] = [
                'label' => '<span class="flag-icon flag-icon-' . $flag . '"></span> ' . $language->name,
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

        $items = ArrayHelper::merge($items, $this->additionalTabs);

        return "<div class=\"nav-tabs-custom {$this->tagClass}\">" . Tabs::widget([
            'items' => $items,
            'options' => $this->options,
            'encodeLabels' => false,
        ]) . $this->footer . '</div>';
    }
}
