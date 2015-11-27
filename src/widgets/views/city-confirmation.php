<?php

use DevGroup\Multilingual\models\CityInterface;
use DevGroup\Multilingual\Multilingual;
use yii\bootstrap\Modal;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/** @var \yii\web\View $this */
/** @var CityInterface $city */
/** @var \DevGroup\Multilingual\Multilingual $multilingual */


$modal = Modal::begin([
    'header' => Multilingual::t('widget', 'Please, confirm your city'),
    'toggleButton' => ['label' => 'click me'],
]); ?>

<?php if ($city === null): ?>
    <?= Multilingual::t('widget', 'We could not identify your city. Please, choose one from the list:'); ?>
<?php else: ?>
    <?= Multilingual::t(
        'widget',
        'We have identified your city as "{name}" . Please, confirm or select another city:',
        [
            'name' => $city->getName()
        ]
    ) ?>
<?php endif; ?>
    <ul>
        <?php foreach ($allCites as $item): ?>
            <?php if (empty($city) || $item->id !== $city->id): ?>
                <li><?= Html::a(
                        $item->getName(),
                        ArrayHelper::merge(
                            [Yii::$app->requestedRoute],
                            Yii::$app->request->getQueryParams(),
                            [
                                'multilingual-city-id' => $item->getId(),
                            ]
                        )
                    ); ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
<?php Modal::end(); ?>
<?php
$this->registerJs("$('#" . $modal->getId() . "').modal('show');");
