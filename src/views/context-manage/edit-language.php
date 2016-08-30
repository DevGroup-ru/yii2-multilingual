<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var boolean $hasAccess
 * @var DevGroup\Multilingual\models\Language $model
 * @var yii\web\View $this
 * @codeCoverageIgnore
 */

$this->title = Yii::t('app', $model->isNewRecord ? 'Create' : 'Update');
$this->params['breadcrumbs'] = [
    [
        'label' => Yii::t('app', 'Contexts'),
        'url' => ['index']
    ],
    [
        'label' => Yii::t('app', 'Context') . ' #' . $model->context_id,
        'url' => ['edit', 'id' => $model->context_id]
    ],
    Yii::t('app', 'Update')
];

?>
<?php $form = ActiveForm::begin(); ?>
<div class="box">
    <div class="box-body">
        <div class="row">
            <div class="col-xs-12 col-md-6">
                <?= $form->field($model, 'name') ?>
                <?= $form->field($model, 'name_native') ?>
                <?= $form->field($model, 'iso_639_1') ?>
                <?= $form->field($model, 'iso_639_2t') ?>
                <?= $form->field($model, 'yii_language') ?>
            </div>
            <div class="col-xs-12 col-md-6">
                <?= $form->field($model, 'hreflang') ?>
                <?= $form->field($model, 'domain') ?>
                <?= $form->field($model, 'folder') ?>
                <?= $form->field($model, 'sort_order') ?>
            </div>
        </div>
    </div>
    <div class="box-footer">
        <?php if ($hasAccess) : ?>
            <div class="form-group pull-right">
                <?= Html::submitButton(
                    $model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'),
                    ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary'])
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php ActiveForm::end(); ?>
