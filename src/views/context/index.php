<?php

use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var DevGroup\Multilingual\models\Context $model
 * @var yii\web\View $this
 * @codeCoverageIgnore
 */

$this->title = Yii::t('app', 'Contexts');
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="context-index">
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
                'domain',
                'tree_root_id',
                [
                    'class' => \DevGroup\AdminUtils\columns\ActionColumn::class,
                ],
            ],
        ]
    );
    ?>
</div>
