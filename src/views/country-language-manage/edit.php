<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var boolean $hasAccess
 * @var DevGroup\Multilingual\models\CountryLanguage $model
 * @var yii\web\View $this
 * @codeCoverageIgnore
 */

$this->title = Yii::t('app', $model->isNewRecord ? 'Create' : 'Update');
$this->params['breadcrumbs'] = [
    ['label' => Yii::t('app', 'Country Languages'), 'url' => ['index']],
    Yii::t('app', 'Update'),
];

?>
<?php $form = ActiveForm::begin(); ?>
<div class="box">
    <div class="box-body">
        <div class="row">
            <div class="col-xs-12 col-md-6">
                <?= $form->field($model, 'name') ?>
                <?= $form->field($model, 'name_native') ?>
            </div>
            <div class="col-xs-12 col-md-6">
                <?= $form->field($model, 'iso_3166_1_alpha_2') ?>
                <?= $form->field($model, 'iso_3166_1_alpha_3') ?>
            </div>
        </div>
    </div>
    <div class="box-footer">
        <?php if ($hasAccess) : ?>
            <div class="form-group pull-right">
                <?=
                Html::submitButton(
                    $model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'),
                    ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
                )
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php ActiveForm::end(); ?>
