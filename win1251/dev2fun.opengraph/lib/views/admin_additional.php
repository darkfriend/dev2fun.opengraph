<?php
/**
 * @author dev2fun <darkfriend>
 * @copyright (c) 2019, darkfriend <hi@darkfriend.ru>
 * @version 1.3.0
 */
/** @var array $arFieldsAdditional */
/** @var array $arOpenGraph */
?>
<table class="adm-detail-content-table edit-table">
    <tr class="heading">
        <td colspan="2">
            <?= \Bitrix\Main\Localization\Loc::getMessage('DEV2FUN_OPENGRAPH_ADMIN_ADDITIONAL_LABEL') ?>
        </td>
    </tr>
    <?php foreach ($arFieldsAdditional as $arField) {
        if (!$arField) continue;
        ?>
        <tr class="adm-detail-valign-top">
            <td width="40%" class="adm-detail-content-cell-l"><?= $arField ?></td>
            <td width="60%" class="adm-detail-content-cell-r">
                <input type="text" name="DEV2FUN_OPENGRAPH[<?= $arField ?>]" value="<?= $arOpenGraph[$arField] ?>">
            </td>
        </tr>
    <?php } ?>
</table>


