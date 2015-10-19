<?php

namespace DevGroup\Multilingual\widgets;

use DevGroup\Multilingual\models\Language;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Class HrefLang is a frontend widget for rendering proper <link> tag with alternate hreflang.
 *
 * Example usage - place it inside your head tag:
 * ```php
 * <?= \DevGroup\Multilingual\widgets\HrefLang::widget() ?>
 * ```
 * @see https://support.google.com/webmasters/answer/189077
 * @see https://yandex.ru/support/webmaster/yandex-indexing/locale-pages.xml (Russian)
 * @see https://yandex.com/support/webmaster/yandex-indexing/localized-markup.xml (English)
 * @package DevGroup\Multilingual\widgets
 */
class HrefLang extends Widget
{
    /**
     * Executes widget
     * @return string link tag with hreflang
     */
    public function run()
    {
        /** @var \DevGroup\Multilingual\Multilingual $multilingual */
        $multilingual = Yii::$app->get('multilingual');
        /** @var Language[] $languages */
        $languages = Language::find()->all();

        $result = '';

        foreach ($languages as $language) {
            if ($language->id === $multilingual->language_id) {
                // skip current language
                continue;
            }
            $result .= Html::tag(
                'link',
                '',
                [
                    'rel' => 'alternate',
                    'hreflang' => $language->iso_639_1,
                    'href' => $multilingual->translateCurrentRequest($language->id),
                ]
            ) . "\n";
        }

        return $result;
    }
}
