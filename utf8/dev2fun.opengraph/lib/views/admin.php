<?php
/**
 * @author dev2fun <darkfriend>
 * @copyright (c) 2017, darkfriend <hi@darkfriend.ru>
 * @version 1.1.0
 */
?>
<table class="adm-detail-content-table edit-table">
    <?php foreach ($arFields as $arField) { ?>
        <?php switch ($arField) {
            case 'url' :
            case 'site_name' :
            case 'image:type' :
            case 'image:width' :
            case 'image:height' :
                break;
            case 'description' : ?>
                <tr class="adm-detail-valign-top">
                    <td width="40%" class="adm-detail-content-cell-l">og:<?= $arField ?></td>
                    <td width="60%" class="adm-detail-content-cell-r">
                        <textarea name="DEV2FUN_OPENGRAPH[<?= $arField ?>]" cols="55" rows="3"
                                  style="width:90%"><?= htmlspecialcharsback($arOpenGraph[$arField]) ?></textarea>
                    </td>
                </tr>
                <?php break; ?>
            <?php case 'image' : ?>
                <tr class="adm-detail-file-row">
                    <td width="40%" class="adm-detail-valign-top adm-detail-content-cell-l">og:<?= $arField ?></td>
                    <td width="60%" class="adm-detail-content-cell-r">
                        <?php if (class_exists('\Bitrix\Main\UI\FileInput', true)) {
                            echo \Bitrix\Main\UI\FileInput::createInstance([
                                "name" => "OG_IMAGE",
                                "description" => true,
                                "upload" => true,
                                "allowUpload" => "I",
                                "medialib" => true,
                                "fileDialog" => true,
                                "cloud" => true,
                                "delete" => true,
                                "maxCount" => 1,
                            ])->show($arOpenGraph[$arField] > 0 ? $arOpenGraph[$arField] : 0);
                        } else {
                            echo CFileInput::Show(
                                "OG_IMAGE",
                                ($arOpenGraph[$arField] > 0 && !$bCopy ? $arOpenGraph[$arField] : 0),
                                [
                                    "IMAGE" => "Y",
                                    "PATH" => "Y",
                                    "FILE_SIZE" => "Y",
                                    "DIMENSIONS" => "Y",
                                    "IMAGE_POPUP" => "Y",
//                                    "MAX_SIZE" => array(
//                                        "W" => COption::GetOptionString("iblock", "detail_image_size"),
//                                        "H" => COption::GetOptionString("iblock", "detail_image_size"),
//                                    ),
                                ],
                                [
                                    'upload' => true,
                                    'medialib' => true,
                                    'file_dialog' => true,
                                    'cloud' => true,
                                    'del' => true,
                                    'description' => true,
                                ]
                            );
                        } ?>
                    </td>
                </tr>
                <?php break; ?>
            <?php default : ?>
                <tr class="adm-detail-valign-top">
                    <td width="40%" class="adm-detail-content-cell-l">og:<?= $arField ?></td>
                    <td width="60%" class="adm-detail-content-cell-r">
                        <input type="text" name="DEV2FUN_OPENGRAPH[<?= $arField ?>]"
                               value="<?= $arOpenGraph[$arField] ?>">
                    </td>
                </tr>
            <?php } ?>
    <?php } ?>
</table>


