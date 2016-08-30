<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var \yii\data\ActiveDataProvider $dataProvider
 * @var boolean $hasAccess
 * @var DevGroup\Multilingual\models\Context $model
 * @var yii\web\View $this
 * @codeCoverageIgnore
 */

$this->title = Yii::t('app', $model->isNewRecord ? 'Create' : 'Update');
$this->params['breadcrumbs'] = [
    ['label' => Yii::t('app', 'Contexts'), 'url' => ['index']],
    Yii::t('app', 'Update')
];

?>
<?php $form = ActiveForm::begin(); ?>
<div class="box">
    <div class="box-body">
        <div class="context-form">
            <?= $form->field($model, 'name') ?>
            <?= $form->field($model, 'domain') ?>
            <?=
            $form->field($model, 'tree_root_id')
                ->dropDownList(call_user_func([Yii::$app->multilingual->modelsMap['Tree'], 'getTreeRootsList']))
            ?>

        </div>
    </div>
    <div class="box-footer">
        <div class="pull-right">
            <?php if ($hasAccess) : ?>
                <div class="form-group">
                    <?= Html::submitButton(
                        $model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'),
                        ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary'])
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php ActiveForm::end(); ?>

<?php if (!$model->isNewRecord && Yii::$app->user->can('multilingual-view-language')): ?>
<div class="box">
    <div class="box-header">
        <h2><?= Yii::t('app', 'Languages') ?></h2>
    </div>
    <div class="box-body">
        <?=
        \yii\grid\GridView::widget(
            [
                'dataProvider' => $dataProvider,
                'columns' => [
                    'id',
                    'name',
                    'name_native',
                    'iso_639_1',
                    'iso_639_2t',
                    'hreflang',
                    'domain',
                    'folder',
                    'yii_language',
                    'sort_order',
                    [
                        'buttons' => [
                            'edit' => [
                                'url' => 'edit-language',
                                'icon' => 'pencil',
                                'class' => 'btn-primary',
                                'label' => Yii::t('app', 'Edit'),
                            ],
                            'delete' => [
                                'url' => 'delete-language',
                                'icon' => 'trash-o',
                                'class' => 'btn-danger',
                                'label' => Yii::t('app', 'Delete'),
                                'options' => [
                                    'data-action' => 'delete',
                                ],
                            ],
                        ],
                        'class' => \DevGroup\AdminUtils\columns\ActionColumn::class,
                    ],
                ],
            ]
        )
        ?>
    </div>
    <div class="box-footer">
        <div class="pull-right">
            <?php if (Yii::$app->user->can('multilingual-create-language')): ?>
                <p>
                    <?= Html::a(Yii::t('app', 'Create'), ['edit-language', 'contextId' => $model->id], ['class' => 'btn btn-success']) ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

