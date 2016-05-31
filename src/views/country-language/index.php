<?php

use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var DevGroup\Multilingual\models\CountryLanguage $model
 * @var yii\web\View $this
 * @codeCoverageIgnore
 */

$this->title = Yii::t('app', 'Country Languages');
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="country-language-index">
    <p>
        <?= Html::a(Yii::t('app', 'Create'), ['edit'], ['class' => 'btn btn-success']) ?>
    </p>
    <?=
    GridView::widget(
        [
            'dataProvider' => $dataProvider,
            'filterModel' => $model,
            'columns' => [
                'id',
                'name',
                'name_native',
                'iso_3166_1_alpha_2',
                 'iso_3166_1_alpha_3',
                ['class' => \DevGroup\AdminUtils\columns\ActionColumn::class],
            ],
        ]
    )
    ?>
</div>
