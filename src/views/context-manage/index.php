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
<div class="box">
    <div class="box-body">
        <div class="context-index">
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
    </div>
    <div class="box-footer">
        <?php if (Yii::$app->user->can('multilingual-create-context')) : ?>
            <div class="pull-right">
                <?= Html::a(Yii::t('app', 'Create'), ['edit'], ['class' => 'btn btn-success']) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
