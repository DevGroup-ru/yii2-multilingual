<?php
use yii\bootstrap\Tabs;

/** @var \yii\web\View $this */
/** @var array $tabs */
/** @var array $options */
/**  @codeCoverageIgnore */


echo Tabs::widget(
    [
        'items' => $tabs,
        'options' => $options,
        'encodeLabels' => false,
    ]
);