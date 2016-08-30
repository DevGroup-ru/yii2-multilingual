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
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Contexts'), 'url' => ['edit', 'id' => $model->context_id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');

?>
<div class="context-update">
    <div class="context-form">
        <?php $form = ActiveForm::begin(); ?>
        <?= $form->field($model, 'name') ?>
        <?= $form->field($model, 'name_native') ?>
        <?= $form->field($model, 'iso_639_1') ?>
        <?= $form->field($model, 'iso_639_2t') ?>
        <?= $form->field($model, 'hreflang') ?>
        <?= $form->field($model, 'domain') ?>
        <?= $form->field($model, 'folder') ?>
        <?= $form->field($model, 'yii_language') ?>
        <?= $form->field($model, 'sort_order') ?>
        <?php if ($hasAccess) : ?>
            <div class="form-group">
                <?= Html::submitButton(
                    $model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'),
                    ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary'])
                ?>
            </div>
        <?php endif; ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>
