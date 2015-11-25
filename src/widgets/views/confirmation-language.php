<?php

use DevGroup\Multilingual\models\Language;
use DevGroup\Multilingual\Multilingual;
use yii\bootstrap\Modal;
use yii\helpers\Html;

/** @var \yii\web\View $this */
/** @var Language[] $languages */
/** @var integer $currentLanguageId */
/** @var \DevGroup\Multilingual\Multilingual $multilingual */


$modal = Modal::begin([
    'header' => Html::tag('h4', Multilingual::t('widget', 'Please, confirm language')),
    'toggleButton' => ['label' => 'click me'],
]); ?>

    <p>
        <?= Multilingual::t('widget', 'Your language is "{name}". Please, confirm or select another language:',
            ['name' => $languages[$currentLanguageId]->name]) ?>
    </p>
    <ul>
        <?php foreach ($languages as $language) : ?>
            <?php if ($language->id !== $currentLanguageId): ?>
                <li>
                    <a href="<?= $multilingual->translateCurrentRequest($language->id) ?>">
                        <?= $language->name ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach;
        ?>
    </ul>
<?php Modal::end(); ?>
<?php
$this->registerJs("$('#" . $modal->getId() . "').modal('show');");
