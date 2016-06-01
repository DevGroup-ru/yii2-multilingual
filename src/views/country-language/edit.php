<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View
 * @var $model DevGroup\Multilingual\models\CountryLanguage
 * @codeCoverageIgnore
 */

$this->title = Yii::t('app', $model->isNewRecord ? 'Create' : 'Update');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Country Languages'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');

?>
<div class="country-language-update">
    <div class="country-language-form">
        <?php $form = ActiveForm::begin(); ?>
        <?= $form->field($model, 'name') ?>
        <?= $form->field($model, 'name_native') ?>
        <?= $form->field($model, 'iso_3166_1_alpha_2') ?>
        <?= $form->field($model, 'iso_3166_1_alpha_3') ?>
        <div class="form-group">
            <?=
            Html::submitButton(
                $model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'),
                ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
            )
            ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
