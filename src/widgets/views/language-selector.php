<?php
use yii\helpers\Html;

/** @var DevGroup\Multilingual\models\Language[] $languages */
/** @var integer $currentLanguageId */
/** @var \DevGroup\Multilingual\Multilingual $multilingual */
/** @var \yii\web\View $this */
/** @var string $blockClass */
/** @var string $blockId */
?>
<div class="<?= $blockClass ?>" id="<?=$blockId?>">
    <a class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" href="#">
        <?= $languages[$currentLanguageId]->name ?> <span class="caret"></span>
    </a>
    <ul class="dropdown-menu">
<?php
foreach ($languages as $language) :
    if ($language->id === $currentLanguageId) {
        continue;
    }
?>
        <li>
            <a href="<?= $multilingual->translateCurrentRequest($language->id) ?>">
                <?= $language->name ?>
            </a>
        </li>
<?php
endforeach;
?>
    </ul>
</div>
