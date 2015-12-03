<?php

use DevGroup\Multilingual\models\CityInterface;
use DevGroup\Multilingual\models\CountryLanguageInterface;
use DevGroup\Multilingual\Multilingual;
use yii\bootstrap\Modal;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/** @var \yii\web\View $this */
/** @var CityInterface $city */
/** @var CityInterface[] $allCites */
/** @var CountryLanguageInterface $country */
/** @var \DevGroup\Multilingual\Multilingual $multilingual */
?>

<div id="select_geo_block" class="sidebar-module sidebar-module-inset">

    <p id="ip">
        <?= $multilingual->getIp(); ?>
    </p>

    <p>
        <span class="city">
        <?php if ($city === null): ?>
            <?= Multilingual::t('widget', 'We could not identify your city.'); ?>
        <?php else: ?>
            <?= Multilingual::t(
                'widget',
                'We have identified your city as "{name}".',
                [
                    'name' => $city->getName()
                ]
            ) ?>
        <?php endif; ?>
        </span>
        <span class="country">
            <?php if ($country !== null): ?>
                (<?= $country->name; ?>)
            <?php endif; ?>
        </span>
    </p>

    <?php $modal = Modal::begin([
        'header' => Multilingual::t('widget', 'Please, confirm your city'),
        'toggleButton' => ['label' => Multilingual::t('widget', 'Set city')],
    ]); ?>
    <?php if ($city === null): ?>
        <?= Multilingual::t('widget', 'We could not identify your city.'); ?>
        <?= Multilingual::t('widget', 'Please, choose one from the list:'); ?>
    <?php else: ?>
        <?= Multilingual::t(
            'widget',
            'We have identified your city as "{name}".',
            [
                'name' => $city->getName()
            ]
        ) ?>
        <?= Multilingual::t('widget', 'Please, confirm or select another city:'); ?>
    <?php endif; ?>
    <ul>
        <?php foreach ($allCites as $item): ?>
            <?php if (empty($city) || $item->getId() !== $city->getId()): ?>
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
    if ($multilingual->needsConfirmation === false && $multilingual->cityNeedsConfirmation) {
        $this->registerJs("$('#" . $modal->getId() . "').modal('show');");
    }
    ?>
</div>

