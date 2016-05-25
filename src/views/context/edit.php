<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var DevGroup\Multilingual\models\Context $model
 * @var yii\web\View $this
 */

$this->title = Yii::t('app', $model->isNewRecord ? 'Create' : 'Update');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Contexts'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');

?>
<div class="context-update">
    <div class="context-form">
        <?php $form = ActiveForm::begin(); ?>
        <?= $form->field($model, 'name') ?>
        <?= $form->field($model, 'domain') ?>
        <?=
        $form->field($model, 'tree_root_id')
            ->dropDownList(['123' => 'The first root', '666' => 'Root umber two', '1337' => 'Another root'])
        ?>
        <div class="form-group">
            <?= Html::submitButton(
                $model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'),
                ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary'])
            ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
    <?php if (!$model->isNewRecord): ?>
        <h2><?= Yii::t('app', 'Languages') ?></h2>
        <p>
            <?= Html::a(Yii::t('app', 'Create'), ['edit-language', 'contextId' => $model->id], ['class' => 'btn btn-success']) ?>
        </p>
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
                    // 'context_id',
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
    <?php endif; ?>
</div>
