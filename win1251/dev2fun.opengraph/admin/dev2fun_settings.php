<?php
/**
 * @author dev2fun <darkfriend>
 * @copyright (c) 2019-2023, darkfriend <hi@darkfriend.ru>
 * @version 1.4.2
 * @global CUser $USER
 * @global CMain $APPLICATION
 */

define("ADMIN_MODULE_NAME", "dev2fun.opengraph");
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
CModule::IncludeModule("iblock");
CModule::IncludeModule("dev2fun.opengraph");

use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

IncludeModuleLangFile($GLOBALS['reqPath']);

$canRead = $USER->CanDoOperation('d2f_og_settings_read');
$canWrite = $USER->CanDoOperation('d2f_og_settings_write');
if (!$canRead && !$canWrite) $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$EDITION_RIGHT = $APPLICATION->GetGroupRight(dev2funModuleOpenGraphClass::$module_id);
if ($EDITION_RIGHT === "D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$aTabs = [
    [
        "DIV" => "main",
        "TAB" => Loc::getMessage("SEC_MAIN_TAB"),
        "ICON" => "main_user_edit",
        "TITLE" => Loc::getMessage("SEC_MAIN_TAB_TITLE"),
    ],
    [
        "DIV" => "donate",
        "TAB" => Loc::getMessage('SEC_DONATE_TAB'),
        "ICON" => "main_user_edit",
        "TITLE" => Loc::getMessage('SEC_DONATE_TAB_TITLE'),
    ],
];

$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);
$bVarsFromForm = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_REQUEST['CLEAR_CACHE']) && check_bitrix_sessid()) {
    dev2funModuleOpenGraphClass::clearCache(true);
    LocalRedirect($APPLICATION->GetCurPageParam(
        'action=settings&cache_success=Y',
        ['save_success', 'cache_success', 'status', 'action']
    ));
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $_REQUEST["save"] . $_REQUEST["apply"] . $_REQUEST["FIELDS"] != "" && $canWrite && check_bitrix_sessid()) {
    if (!empty($_REQUEST["FIELDS"]['og'])) {
        $ogFields = $_REQUEST["FIELDS"]['og'];
        $ogFields = array_unique($ogFields);
        dev2funModuleOpenGraphClass::setFields($ogFields);
    }

    if (!empty($_REQUEST['FIELDS_ADDIT'])) {
        //		$ogFields = $_REQUEST['FIELDS_ADDIT'];
        //		$ogFields = array_unique($ogFields);
        dev2funModuleOpenGraphClass::setFieldsAdditional($_REQUEST['FIELDS_ADDIT']);
    }

    if (!empty($_REQUEST["OGSETTINGS"])) {
        $sFields = $_REQUEST["OGSETTINGS"];
        dev2funModuleOpenGraphClass::setSettingFields($sFields);
    }

    if (!empty($_REQUEST["EXCLUDE_PAGE"])) {
        $sFields = $_REQUEST["EXCLUDE_PAGE"];
        dev2funModuleOpenGraphClass::getInstance()->setSettingsExcludePage($sFields);
    }

    $enableTabElement = empty($_REQUEST["ADDTAB_ELEMENT"]) ? 'N' : $_REQUEST["ADDTAB_ELEMENT"];
    Option::set(dev2funModuleOpenGraphClass::$module_id, 'ADDTAB_ELEMENT', $enableTabElement);

    $enableTabSection = empty($_REQUEST["ADDTAB_SECTION"]) ? 'N' : $_REQUEST["ADDTAB_SECTION"];
    Option::set(dev2funModuleOpenGraphClass::$module_id, 'ADDTAB_SECTION', $enableTabSection);

    $removeIndex = empty($_REQUEST["REMOVE_INDEX"]) ? 'N' : $_REQUEST["REMOVE_INDEX"];
    Option::set(dev2funModuleOpenGraphClass::$module_id, 'REMOVE_INDEX', $removeIndex);

    $sOptionValue = empty($_REQUEST["SHOW_IN_ELEMENTS"]) ? 'N' : $_REQUEST["SHOW_IN_ELEMENTS"];
    Option::set(dev2funModuleOpenGraphClass::$module_id, 'SHOW_IN_ELEMENTS', $sOptionValue);

    $sOptionValue = empty($_REQUEST["SHOW_IN_SECTIONS"]) ? 'N' : $_REQUEST["SHOW_IN_SECTIONS"];
    Option::set(dev2funModuleOpenGraphClass::$module_id, 'SHOW_IN_SECTIONS', $sOptionValue);

    $enableAutoAddTitle = empty($_REQUEST["AUTO_ADD_TITLE"]) ? 'N' : $_REQUEST["AUTO_ADD_TITLE"];
    Option::set(dev2funModuleOpenGraphClass::$module_id, 'AUTO_ADD_TITLE', $enableAutoAddTitle);

    $enableAutoAddDescription = empty($_REQUEST["AUTO_ADD_DESCRIPTION"]) ? 'N' : $_REQUEST["AUTO_ADD_DESCRIPTION"];
    Option::set(dev2funModuleOpenGraphClass::$module_id, 'AUTO_ADD_DESCRIPTION', $enableAutoAddDescription);

    $enableAutoAddImage = empty($_REQUEST["AUTO_ADD_IMAGE"]) ? 'N' : $_REQUEST["AUTO_ADD_IMAGE"];
    Option::set(dev2funModuleOpenGraphClass::$module_id, 'AUTO_ADD_IMAGE', $enableAutoAddImage);

    if (isset($_POST["DEFAULT_IMAGE_del"]) && !is_array($_POST["DEFAULT_IMAGE"])) {
        \CFile::Delete($_POST["DEFAULT_IMAGE"]);
        Option::set(dev2funModuleOpenGraphClass::$module_id, 'DEFAULT_IMAGE', '');
    }

    if (!empty($_POST["DEFAULT_IMAGE"])) {
        $_POST['DEFAULT_IMAGE'] = \CIBlock::makeFileArray($_POST['DEFAULT_IMAGE']);
        $fileID = \CFile::SaveFile($_POST['DEFAULT_IMAGE'], 'dev2fun_opengraph', true);
        if ($fileID) {
            Option::set(dev2funModuleOpenGraphClass::$module_id, 'DEFAULT_IMAGE', $fileID);
        }
    }

    if (!empty($_POST['OG_SETTINGS_RESIZE'])) {
        $sResize = $_POST['OG_SETTINGS_RESIZE'];
        if (empty($sResize['TYPE'])) {
            $sResize['TYPE'] = BX_RESIZE_IMAGE_PROPORTIONAL;
        }
        if ($sResize['ENABLE'] !== 'Y') {
            $sResize['ENABLE'] = 'N';
        }
        Option::set(dev2funModuleOpenGraphClass::$module_id, 'RESIZE_IMAGE', serialize($sResize));
    }

    if (!empty($_POST['sortableOpenGraph'])) {
        $sortable = $_POST['sortableOpenGraph'];
        $sortable = explode(',', $sortable);
        Option::set(dev2funModuleOpenGraphClass::$module_id, 'SORTABLE', serialize($sortable));
    }

    //	LocalRedirect($_SERVER['PHP_SELF'] . "?action=settings&status=success&lang=" . LANGUAGE_ID . "&" . $tabControl->ActiveTabParam());
    LocalRedirect($APPLICATION->GetCurPageParam(
        'action=settings&status=success&' . $tabControl->ActiveTabParam(),
        ['status', 'cache_success', 'action']
    ));
}

$ogFields = dev2funModuleOpenGraphClass::getFields();
if (!$ogFields) {
    $ogFields = dev2funModuleOpenGraphClass::$arReqOG;
}
$ogFieldsAdditional = dev2funModuleOpenGraphClass::getFieldsAdditional();
$settingFields = dev2funModuleOpenGraphClass::getSettingFields();

$excludedPages = dev2funModuleOpenGraphClass::getInstance()->getSettingsExcludePage();

$enableTabElement = Option::get(dev2funModuleOpenGraphClass::$module_id, 'ADDTAB_ELEMENT', 'N');
if ($enableTabElement !== 'Y') $enableTabElement = false;

$enableTabSection = Option::get(dev2funModuleOpenGraphClass::$module_id, 'ADDTAB_SECTION', 'N');
if ($enableTabSection !== 'Y') $enableTabSection = false;

$removeIndex = Option::get(dev2funModuleOpenGraphClass::$module_id, 'REMOVE_INDEX', 'N');
if ($removeIndex !== 'Y') $removeIndex = false;

$showElements = Option::get(dev2funModuleOpenGraphClass::$module_id, 'SHOW_IN_ELEMENTS', 'N');
if ($showElements !== 'Y') $showElements = false;

$showSections = Option::get(dev2funModuleOpenGraphClass::$module_id, 'SHOW_IN_SECTIONS', 'N');
if ($showSections != 'Y') $showSections = false;

$enableAutoAddTitle = Option::get(dev2funModuleOpenGraphClass::$module_id, 'AUTO_ADD_TITLE', 'N');
if ($enableAutoAddTitle !== 'Y') $enableAutoAddTitle = false;

$enableAutoAddDescription = Option::get(dev2funModuleOpenGraphClass::$module_id, 'AUTO_ADD_DESCRIPTION', 'N');
if ($enableAutoAddDescription !== 'Y') $enableAutoAddDescription = false;

$enableAutoAddImage = Option::get(dev2funModuleOpenGraphClass::$module_id, 'AUTO_ADD_IMAGE', 'N');
if ($enableAutoAddImage !== 'Y') $enableAutoAddImage = false;

$defaultImage = Option::get(dev2funModuleOpenGraphClass::$module_id, 'DEFAULT_IMAGE', 0);

$arSettingResize = Option::get(dev2funModuleOpenGraphClass::$module_id, 'RESIZE_IMAGE');
if (empty($arSettingResize)) {
    $arSettingResize = [
        'ENABLE' => 'N',
        'TYPE' => BX_RESIZE_IMAGE_PROPORTIONAL,
        'WIDTH' => '',
        'HEIGHT' => '',
    ];
} else {
    $arSettingResize = unserialize($arSettingResize, ["allowed_classes" => false]);
}
if (!isset($arSettingResize['TYPE'])) {
    $arSettingResize['TYPE'] = BX_RESIZE_IMAGE_PROPORTIONAL;
}

$arSortableDefault = [
    'og_fields' => Loc::getMessage('LABEL_SETTING_OG_SORTABLE_PROP_OG'),
    'iblock_fields' => Loc::getMessage('LABEL_SETTING_OG_SORTABLE_PROP_IBLOCK'),
    'prop_fields' => Loc::getMessage('LABEL_SETTING_OG_SORTABLE_PROP_FIELDS'),
    'default' => Loc::getMessage('LABEL_SETTING_OG_SORTABLE_PROP_DEFAULT'),
];
$arSortable = Option::get(dev2funModuleOpenGraphClass::$module_id, 'SORTABLE');
if (!$arSortable) {
    $arSortable = array_keys($arSortableDefault);
} else {
    $arSortable = unserialize($arSortable, ["allowed_classes" => false]);
}

require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/prolog_admin_after.php");

$assets = \Bitrix\Main\Page\Asset::getInstance();
$assets->addJs('/bitrix/js/' . dev2funModuleOpenGraphClass::$module_id . '/Sortable.min.js');
$assets->addJs('/bitrix/js/' . dev2funModuleOpenGraphClass::$module_id . '/script.js');

if (!empty($_REQUEST['status']) && $_REQUEST['status'] === 'success') {
    \CAdminMessage::showMessage([
        'TYPE' => 'OK',
        'MESSAGE' => GetMessage("SUCCESS_SAVE_MESSAGE"),
    ]);
}
if (!empty($_REQUEST['cache_success'])) {
    \CAdminMessage::showMessage([
        "MESSAGE" => Loc::getMessage("D2F_OG_CACHE_CLEARED"),
        "TYPE" => "OK",
    ]);
}
$serverUrl = dev2funModuleOpenGraphClass::getInstance()->getUrl('/');
?>
    <link rel="stylesheet" href="<?= '/bitrix/css/' . dev2funModuleOpenGraphClass::$module_id . '/opengraph.css' ?>">
    <link rel="stylesheet" href="https://unpkg.com/blaze@4.0.0-6/scss/dist/components.cards.min.css">
    <link rel="stylesheet" href="https://unpkg.com/blaze@4.0.0-6/scss/dist/objects.grid.min.css">
    <link rel="stylesheet" href="https://unpkg.com/blaze@4.0.0-6/scss/dist/objects.grid.responsive.min.css">
    <link rel="stylesheet" href="https://unpkg.com/blaze@4.0.0-6/scss/dist/objects.containers.min.css">
    <link rel="stylesheet" href="https://unpkg.com/blaze@4.0.0-6/scss/dist/components.tables.min.css">
    <form method="POST" action="?action=save&lang=<?php echo LANGUAGE_ID ?>&<?= $tabControl->ActiveTabParam() ?>"
          enctype="multipart/form-data" name="editform">
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="lang" value="<?= LANG ?>">
        <?php
        $tabControl->Begin();
        $tabControl->BeginNextTab();

        //$isAction = COption::GetOptionString(dev2funModuleOpenGraphClass::$module_id, "edition_files_action");
        ?>
        <tr>
            <td colspan="2" align="left">
                <table class="adm-detail-content-table edit-table">

                    <tr>
                        <td class="adm-detail-content-cell-l">
                            <label><?= Loc::getMessage("LABEL_TITLE_OG_FIELDS"); ?>:</label>
                        </td>
                        <td width="60%" class="adm-detail-content-cell-r">
                            <table class="nopadding" cellpadding="0" cellspacing="0" border="0" width="100%"
                                   id="d2f_fields_og">
                                <tbody>
                                <?php foreach ($ogFields as $key => $field):
                                    $key = str_replace('n', '', $key);
                                    ?>
                                    <tr>
                                        <td>
                                            <label>og:</label>
                                            <input name="FIELDS[og][n<?= $key ?>]" value="<?= $field ?>" size="30"
                                                   type="text"><br>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td>
                                        <label>og:</label>
                                        <input name="FIELDS[og][n<?= count($ogFields) ?>]" value="" size="30"
                                               type="text"><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="button" value="<?= Loc::getMessage("LABEL_ADD"); ?>"
                                               onclick="addNewRow('d2f_fields_og')">
                                    </td>
                                </tr>
                                <script type="text/javascript">
                                    BX.addCustomEvent('onAutoSaveRestore', function (ob, data) {
                                        for (var i in data) {
                                            if (i.substring(0, 9) == 'FIELDS[og][') {
                                                addNewRow('d2f_fields_og')
                                            }
                                        }
                                    });
                                </script>
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="adm-detail-content-cell-l">
                            <label><?= Loc::getMessage("LABEL_TITLE_OG_FIELDS"); ?>:</label>
                        </td>
                        <td width="60%" class="adm-detail-content-cell-r">
                            <table class="nopadding" cellpadding="0" cellspacing="0" border="0" width="100%"
                                   id="d2f_fields_additional">
                                <tbody>
                                <?php foreach ($ogFieldsAdditional as $key => $field):
                                    $key = str_replace('n', '', $key);
                                    ?>
                                    <tr>
                                        <td>
                                            <input name="FIELDS_ADDIT[n<?= $key ?>]" value="<?= $field ?>" size="30"
                                                   type="text"
                                                   placeholder="<?= Loc::getMessage("LABEL_SETTING_OG_ADDITIONAL_PLACEHOLDER"); ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php //$cntFieldsAdditional = count($ogFieldsAdditional); ?>
                                <tr>
                                    <td>
                                        <input name="FIELDS_ADDIT[n<?= count($ogFieldsAdditional) ?>]" value=""
                                               size="30" type="text"
                                               placeholder="<?= Loc::getMessage("LABEL_SETTING_OG_ADDITIONAL_PLACEHOLDER"); ?>">
                                    </td>
                                    <!--									<td>-->
                                    <!--										<input name="FIELDS_ADDIT[n-->
                                    <?php //=$cntFieldsAdditional?><!--][value]" value="" size="100" type="text" placeholder="-->
                                    <?php //=Loc::getMessage("LABEL_SETTING_OG_ADDITIONAL_PLACEHOLDER_VALUE");?><!--">-->
                                    <!--									</td>-->
                                </tr>
                                <tr>
                                    <td>
                                        <input type="button" value="<?= Loc::getMessage("LABEL_ADD"); ?>"
                                               onclick="addNewRow('d2f_fields_additional')">
                                    </td>
                                </tr>
                                <script type="text/javascript">
                                    BX.addCustomEvent('onAutoSaveRestore', function (ob, data) {
                                        for (var i in data) {
                                            if (i.substring(0, 9) == 'FIELDS_ADDIT[') {
                                                addNewRow('d2f_fields_additional')
                                            }
                                        }
                                    });
                                </script>
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td></td>
                        <td>
                            <?php
                            echo BeginNote();
                            echo Loc::getMessage('LABEL_TITLE_OG_FIELDS_TEXT');
                            EndNote();
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td class="adm-detail-content-cell-l">
                            <label><?= Loc::getMessage("LABEL_TITLE_OG_PAGE_EXCLUDED"); ?>:</label>
                        </td>
                        <td width="60%" class="adm-detail-content-cell-r">
                            <table class="nopadding" cellpadding="0" cellspacing="0" border="0" width="100%"
                                   id="d2f_page_excluded_og">
                                <tbody>
                                <?php foreach ($excludedPages as $key => $page):
                                    $key = str_replace('n', '', $key);
                                    ?>
                                    <tr>
                                        <td>
                                            <label><?= $serverUrl ?></label>
                                            <input name="EXCLUDE_PAGE[n<?= $key ?>]" value="<?= $page ?>" size="30"
                                                   type="text">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td>
                                        <label><?= $serverUrl ?></label>
                                        <input name="EXCLUDE_PAGE[n<?= count($excludedPages) ?>]" value="" size="30"
                                               type="text">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="button" value="<?= Loc::getMessage("LABEL_ADD"); ?>"
                                               onclick="addNewRow('d2f_page_excluded_og')">
                                    </td>
                                </tr>
                                <script type="text/javascript">
                                    BX.addCustomEvent('onAutoSaveRestore', function (ob, data) {
                                        for (var i in data) {
                                            if (i.substring(0, 9) == 'EXCLUDE_PAGE[') {
                                                addNewRow('d2f_page_excluded_og')
                                            }
                                        }
                                    });
                                </script>
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td></td>
                        <td>
                            <?php
                            echo BeginNote();
                            echo Loc::getMessage('LABEL_TITLE_OG_PAGE_EXCLUDED_TEXT');
                            EndNote();
                            ?>
                        </td>
                    </tr>

                    <tr class="heading">
                        <td colspan="2"><b><?= Loc::getMessage('LABEL_TITLE_OG_SETTINGS_ADMIN') ?></b></td>
                    </tr>

                    <tr>
                        <td class="adm-detail-content-cell-l">
                            <table class="nopadding" cellpadding="0" cellspacing="4" border="0" width="100%">
                                <tbody>
                                <tr>
                                    <td>
                                        <label
                                            for="ADDTAB_ELEMENT"><?= Loc::getMessage('LABEL_SETTING_ADD_TAB_ELEMENT') ?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label
                                            for="ADDTAB_SECTION"><?= Loc::getMessage('LABEL_SETTING_ADD_TAB_SECTION') ?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label
                                            for="AUTO_ADD_TITLE"><?= Loc::getMessage('LABEL_SETTING_ADD_TITLE') ?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label
                                            for="AUTO_ADD_DESCRIPTION"><?= Loc::getMessage('LABEL_SETTING_ADD_DESCRIPTION') ?></label>
                                    </td>
                                </tr>
                                <?php if (in_array('image', $ogFields)) { ?>
                                    <tr>
                                        <td>
                                            <label
                                                for="AUTO_ADD_IMAGE"><?= Loc::getMessage('LABEL_SETTING_ADD_IMAGE') ?></label>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </td>
                        <td width="60%" class="adm-detail-content-cell-r" colspan="">
                            <table class="nopadding" cellpadding="0" cellspacing="4" border="0" width="100%">
                                <tbody>
                                <tr>
                                    <td>
                                        <input type="checkbox" id="ADDTAB_ELEMENT" name="ADDTAB_ELEMENT"
                                               value="Y" <?= $enableTabElement ? 'checked="checked"' : '' ?>>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="checkbox" id="ADDTAB_SECTION" name="ADDTAB_SECTION"
                                               value="Y" <?= $enableTabSection ? 'checked="checked"' : '' ?>>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="checkbox" id="AUTO_ADD_TITLE" name="AUTO_ADD_TITLE"
                                               value="Y" <?= $enableAutoAddTitle ? 'checked="checked"' : '' ?>>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="checkbox" id="AUTO_ADD_DESCRIPTION" name="AUTO_ADD_DESCRIPTION"
                                               value="Y" <?= $enableAutoAddDescription ? 'checked="checked"' : '' ?>>
                                    </td>
                                </tr>
                                <?php if (in_array('image', $ogFields)) { ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" id="AUTO_ADD_IMAGE" name="AUTO_ADD_IMAGE"
                                                   value="Y" <?= $enableAutoAddImage ? 'checked="checked"' : '' ?>>
                                        </td>
                                    </tr>
                                <?php } ?>

                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <tr class="heading">
                        <td colspan="2"><b><?= Loc::getMessage('LABEL_TITLE_OG_SETTINGS_SHOW') ?></b></td>
                    </tr>

                    <tr>
                        <td class="adm-detail-content-cell-l">
                            <table class="nopadding" cellpadding="0" cellspacing="4" border="0" width="100%">
                                <tbody>
                                <tr>
                                    <td>
                                        <label
                                            for="REMOVE_INDEX"><?= Loc::getMessage('LABEL_SETTING_ADD_REMOVE_INDEX') ?></label>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                        <td width="60%" class="adm-detail-content-cell-r" colspan="">
                            <table class="nopadding" cellpadding="0" cellspacing="4" border="0" width="100%">
                                <tbody>

                                <tr>
                                    <td>
                                        <input type="checkbox" id="REMOVE_INDEX" name="REMOVE_INDEX"
                                               value="Y" <?= $removeIndex ? 'checked="checked"' : '' ?>>
                                    </td>
                                </tr>

                                </tbody>
                            </table>
                        </td>
                    </tr>


                    <tr>
                        <td class="adm-detail-content-cell-l">
                            <table class="nopadding" cellpadding="0" cellspacing="4" border="0" width="100%">
                                <tbody>
                                <tr>
                                    <td>
                                        <label for="SHOW_IN_ELEMENTS">
                                            <?= Loc::getMessage('LABEL_SETTING_SHOW_ELEMENTS')?>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="SHOW_IN_SECTIONS">
                                            <?= Loc::getMessage('LABEL_SETTING_SHOW_SECTIONS')?>
                                        </label>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                        <td width="60%" class="adm-detail-content-cell-r" colspan="">
                            <table class="nopadding" cellpadding="0" cellspacing="4" border="0" width="100%">
                                <tbody>

                                <tr>
                                    <td>
                                        <input type="checkbox" id="SHOW_IN_ELEMENTS" name="SHOW_IN_ELEMENTS" value="Y" <?= $showElements ? 'checked="checked"' : '' ?>>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="checkbox" id="SHOW_IN_SECTIONS" name="SHOW_IN_SECTIONS" value="Y" <?= $showSections ? 'checked="checked"' : '' ?>>
                                    </td>
                                </tr>

                                </tbody>
                            </table>
                        </td>
                    </tr>


                    <tr style="height: 198px;">
                        <td class="adm-detail-content-cell-l">
                            <table class="nopadding" cellpadding="0" cellspacing="4" border="0" width="100%">
                                <tbody>
                                <tr>
                                    <td>
                                        <label><?= Loc::getMessage('LABEL_SETTING_OG_SORTABLE') ?></label>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                        <td width="60%" class="adm-detail-content-cell-r" colspan="">
                            <table class="nopadding" cellpadding="0" cellspacing="4" border="0" width="100%">
                                <tbody>
                                <tr>
                                    <td>
                                        <div class="sort_container">
                                            <ul id="sort_items" class="sort_list" data-input="sortableOpenGraph">
                                                <?php foreach ($arSortable as $sort) { ?>
                                                    <li data-id="<?= $sort ?>"><?= $arSortableDefault[$sort] ?></li>
                                                <?php } ?>
                                            </ul>
                                            <input type="hidden" value="<?= implode(',', $arSortable) ?>"
                                                   id="sortableOpenGraph"
                                                   name="sortableOpenGraph">
                                        </div>
                                        <br>
                                        <?= BeginNote(); ?>
                                        <?= Loc::getMessage('LABEL_SETTING_OG_SORTABLE_ATTENTION') ?>
                                        <?php EndNote(); ?>
                                        <script type="text/javascript">
                                            initSortable('sort_items');
                                        </script>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>


                    <tr>
                        <td class="adm-detail-content-cell-l">
                            <label
                                for="ADDTAB_ELEMENT"><?= Loc::getMessage('LABEL_SETTING_ADD_DEFAULT_IMAGE') ?></label>
                        </td>
                        <td width="60%" class="adm-detail-content-cell-r" colspan="">
                            <?php if (class_exists('\Bitrix\Main\UI\FileInput', true)) {
                                echo \Bitrix\Main\UI\FileInput::createInstance([
                                    "name" => "DEFAULT_IMAGE",
                                    "description" => true,
                                    "upload" => true,
                                    "allowUpload" => "I",
                                    "medialib" => true,
                                    "fileDialog" => true,
                                    "cloud" => true,
                                    "delete" => true,
                                    "maxCount" => 1,
                                ])->show($defaultImage > 0 ? $defaultImage : 0);
                            } else {
                                echo CFileInput::Show("DEFAULT_IMAGE", ($defaultImage > 0 ? $defaultImage : 0),
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
                                    ], [
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

                    <tr class="heading">
                        <td colspan="2"><b><?= Loc::getMessage('LABEL_SETTING_OG_RESIZE_HEADING') ?></b></td>
                    </tr>
                    <tr>
                        <td class="adm-detail-content-cell-l">
                            <table class="nopadding" cellpadding="0" cellspacing="4" border="0" width="100%">
                                <tbody>
                                <tr>
                                    <td>
                                        <label
                                            for="LABEL_SETTING_OG_RESIZE_ENABLE"><?= Loc::getMessage('LABEL_SETTING_OG_RESIZE_ENABLE') ?></label>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                        <td width="60%" class="adm-detail-content-cell-r" colspan="">
                            <table class="nopadding" cellpadding="0" cellspacing="4" border="0" width="100%">
                                <tbody>
                                <tr>
                                    <td>
                                        <input type="checkbox" id="LABEL_SETTING_OG_RESIZE_ENABLE"
                                               name="OG_SETTINGS_RESIZE[ENABLE]"
                                               value="Y" <?= ($arSettingResize['ENABLE'] == 'Y') ? 'checked' : '' ?>>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="adm-detail-content-cell-l">
                            <table class="nopadding" cellpadding="0" cellspacing="4" border="0" width="100%">
                                <tbody>
                                <tr>
                                    <td>
                                        <label
                                            for="LABEL_SETTING_OG_RESIZE_WIDTH"><?= Loc::getMessage('LABEL_SETTING_OG_RESIZE_WIDTH') ?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label
                                            for="LABEL_SETTING_OG_RESIZE_HEIGHT"><?= Loc::getMessage('LABEL_SETTING_OG_RESIZE_HEIGHT') ?></label>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                        <td width="60%" class="adm-detail-content-cell-r" colspan="">
                            <table class="nopadding" cellpadding="0" cellspacing="4" border="0" width="100%">
                                <tbody>
                                <tr>
                                    <td>
                                        <input type="text" id="LABEL_SETTING_OG_RESIZE_WIDTH"
                                               name="OG_SETTINGS_RESIZE[WIDTH]"
                                               value="<?= isset($arSettingResize['WIDTH']) ? $arSettingResize['WIDTH'] : '' ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="text" id="LABEL_SETTING_OG_RESIZE_HEIGHT"
                                               name="OG_SETTINGS_RESIZE[HEIGHT]"
                                               value="<?= isset($arSettingResize['HEIGHT']) ? $arSettingResize['HEIGHT'] : '' ?>">
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="adm-detail-content-cell-l">
                            <!--                <table class="nopadding" cellpadding="0" cellspacing="4" border="0" width="100%">-->
                            <!--                    <tbody>-->
                            <!--                    <tr>-->
                            <!--                        <td>-->
                            <!--                        </td>-->
                            <!--                    </tr>-->
                            <!--                    </tbody>-->
                            <!--                </table>-->
                        </td>
                        <td width="60%" class="adm-detail-content-cell-r" colspan="">
                            <table class="nopadding" cellpadding="0" cellspacing="4" border="0" width="100%">
                                <tbody>
                                <tr>
                                    <td>
                                        <label>
                                            <input type="radio" name="OG_SETTINGS_RESIZE[TYPE]"
                                                   value="<?= BX_RESIZE_IMAGE_EXACT ?>"
                                                <?= ($arSettingResize['TYPE'] == BX_RESIZE_IMAGE_EXACT) ? 'checked' : '' ?>>
                                            <?= Loc::getMessage('LABEL_SETTING_OG_BX_RESIZE_IMAGE_EXACT') ?>
                                        </label><br>
                                        <label>
                                            <input type="radio" name="OG_SETTINGS_RESIZE[TYPE]"
                                                   value="<?= BX_RESIZE_IMAGE_PROPORTIONAL ?>"
                                                <?= ($arSettingResize['TYPE'] == BX_RESIZE_IMAGE_PROPORTIONAL) ? 'checked' : '' ?>>
                                            <?= Loc::getMessage('LABEL_SETTING_OG_BX_RESIZE_IMAGE_PROPORTIONAL') ?>
                                        </label><br>
                                        <label>
                                            <input type="radio" name="OG_SETTINGS_RESIZE[TYPE]"
                                                   value="<?= BX_RESIZE_IMAGE_PROPORTIONAL_ALT ?>"
                                                <?= ($arSettingResize['TYPE'] == BX_RESIZE_IMAGE_PROPORTIONAL_ALT) ? 'checked' : '' ?>>
                                            <?= Loc::getMessage('LABEL_SETTING_OG_BX_RESIZE_IMAGE_PROPORTIONAL_ALT') ?>
                                        </label>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <tr class="heading">
                        <td colspan="2"><b><?= Loc::getMessage('LABEL_SETTING_OG_CACHE_HEADING') ?></b></td>
                    </tr>
                    <tr>
                        <td class="adm-detail-content-cell-l">
                            <label><?= Loc::getMessage("LABEL_CACHE_TIME_FIELDS"); ?>:</label>
                        </td>
                        <td width="60%" class="adm-detail-content-cell-r">
                            <table class="nopadding" cellpadding="0" cellspacing="0" border="0" width="100%"
                                   id="d2f_fields_settings">
                                <tbody>
                                <tr>
                                    <td>
                                        <input type="text" name="OGSETTINGS[CACHE_TIME]"
                                               value="<?= $settingFields['CACHE_TIME'] ?>"
                                               required min="100">
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="adm-detail-content-cell-l"></td>
                        <td width="60%" class="adm-detail-content-cell-r">
                            <table class="nopadding" cellpadding="0" cellspacing="0" border="0" width="100%"
                                   id="d2f_fields_settings">
                                <tbody>
                                <tr>
                                    <td>
                                        <input type="submit" class="adm-btn adm-btn-cache-delete" name="CLEAR_CACHE"
                                               value="Очистить кэш">
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
        <?php $tabControl->BeginNextTab(); ?>
        <tr>
            <td colspan="2" align="left">
                <div class="o-container--super">
                    <div class="o-grid">
                        <div class="o-grid__cell o-grid__cell--width-70">
                            <div class="c-card">
                                <div class="c-card__body">
                                    <p class="c-paragraph"><?= Loc::getMessage('LABEL_TITLE_HELP_BEGIN') ?>.</p>
                                    <?= Loc::getMessage('LABEL_TITLE_HELP_BEGIN_TEXT'); ?>
                                </div>
                            </div>
                            <div class="o-container--large">
                                <h2 id="yaPay"
                                    class="c-heading u-large"><?= Loc::getMessage('LABEL_TITLE_HELP_DONATE_TEXT'); ?></h2>
                                <iframe
                                    src="https://money.yandex.ru/quickpay/shop-widget?writer=seller&targets=%D0%9F%D0%BE%D0%B4%D0%B4%D0%B5%D1%80%D0%B6%D0%BA%D0%B0%20%D0%BE%D0%B1%D0%BD%D0%BE%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D0%B9%20%D0%B1%D0%B5%D1%81%D0%BF%D0%BB%D0%B0%D1%82%D0%BD%D1%8B%D1%85%20%D0%BC%D0%BE%D0%B4%D1%83%D0%BB%D0%B5%D0%B9&targets-hint=&default-sum=500&button-text=14&payment-type-choice=on&mobile-payment-type-choice=on&hint=&successURL=&quickpay=shop&account=410011413398643"
                                    width="450" height="228" frameborder="0" allowtransparency="true"
                                    scrolling="no"></iframe>
                                <h2 id="morePay"
                                    class="c-heading u-large"><?= Loc::getMessage('LABEL_TITLE_HELP_DONATE_ALL_TEXT'); ?></h2>
                                <table class="c-table">
                                    <tbody class="c-table__body c-table--striped">
                                    <tr class="c-table__row">
                                        <td class="c-table__cell">Yandex.Money</td>
                                        <td class="c-table__cell">410011413398643</td>
                                    </tr>
                                    <tr class="c-table__row">
                                        <td class="c-table__cell">Webmoney WMR (rub)</td>
                                        <td class="c-table__cell">R218843696478</td>
                                    </tr>
                                    <tr class="c-table__row">
                                        <td class="c-table__cell">Webmoney WMU (uah)</td>
                                        <td class="c-table__cell">U135571355496</td>
                                    </tr>
                                    <tr class="c-table__row">
                                        <td class="c-table__cell">Webmoney WMZ (usd)</td>
                                        <td class="c-table__cell">Z418373807413</td>
                                    </tr>
                                    <tr class="c-table__row">
                                        <td class="c-table__cell">Webmoney WME (euro)</td>
                                        <td class="c-table__cell">E331660539346</td>
                                    </tr>
                                    <tr class="c-table__row">
                                        <td class="c-table__cell">Webmoney WMX (btc)</td>
                                        <td class="c-table__cell">X740165207511</td>
                                    </tr>
                                    <tr class="c-table__row">
                                        <td class="c-table__cell">Webmoney WML (ltc)</td>
                                        <td class="c-table__cell">L718094223715</td>
                                    </tr>
                                    <tr class="c-table__row">
                                        <td class="c-table__cell">Webmoney WMH (bch)</td>
                                        <td class="c-table__cell">H526457512792</td>
                                    </tr>
                                    <tr class="c-table__row">
                                        <td class="c-table__cell">PayPal</td>
                                        <td class="c-table__cell"><a href="https://www.paypal.me/darkfriend"
                                                                     target="_blank">paypal.me/@darkfriend</a>
                                        </td>
                                    </tr>
                                    <tr class="c-table__row">
                                        <td class="c-table__cell">Payeer</td>
                                        <td class="c-table__cell">P93175651</td>
                                    </tr>
                                    <tr class="c-table__row">
                                        <td class="c-table__cell">Bitcoin</td>
                                        <td class="c-table__cell">15Veahdvoqg3AFx3FvvKL4KEfZb6xZiM6n</td>
                                    </tr>
                                    <tr class="c-table__row">
                                        <td class="c-table__cell">Litecoin</td>
                                        <td class="c-table__cell">LRN5cssgwrGWMnQruumfV2V7wySoRu7A5t</td>
                                    </tr>
                                    <tr class="c-table__row">
                                        <td class="c-table__cell">Ethereum</td>
                                        <td class="c-table__cell">0xe287Ac7150a087e582ab223532928a89c7A7E7B2</td>
                                    </tr>
                                    <tr class="c-table__row">
                                        <td class="c-table__cell">BitcoinCash</td>
                                        <td class="c-table__cell">
                                            bitcoincash:qrl8p6jxgpkeupmvyukg6mnkeafs9fl5dszft9fw9w
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                                <h2 id="moreThanks"
                                    class="c-heading u-large"><?= Loc::getMessage('LABEL_TITLE_HELP_DONATE_OTHER_TEXT'); ?></h2>
                                <?= Loc::getMessage('LABEL_TITLE_HELP_DONATE_OTHER_TEXT_S'); ?>
                            </div>
                        </div>
                        <div class="o-grid__cell o-grid__cell--width-30">
                            <h2 id="moreThanks"
                                class="c-heading u-large"><?= Loc::getMessage('LABEL_TITLE_HELP_DONATE_FOLLOW'); ?></h2>
                            <table class="c-table">
                                <tbody class="c-table__body">
                                <tr class="c-table__row">
                                    <td class="c-table__cell">
                                        <a href="https://vk.com/dev2fun" target="_blank">vk.com/dev2fun</a>
                                    </td>
                                </tr>
                                <tr class="c-table__row">
                                    <td class="c-table__cell">
                                        <a href="https://facebook.com/dev2fun" target="_blank">facebook.com/dev2fun</a>
                                    </td>
                                </tr>
                                <tr class="c-table__row">
                                    <td class="c-table__cell">
                                        <a href="https://twitter.com/dev2fun" target="_blank">twitter.com/dev2fun</a>
                                    </td>
                                </tr>
                                <tr class="c-table__row">
                                    <td class="c-table__cell">
                                        <a href="https://t.me/dev2fun" target="_blank">telegram/dev2fun</a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <?php
        $tabControl->Buttons(
            [
                "disabled" => (!$canWrite),
            ]
        );
        ?>
        <?php
        $tabControl->End();
        ?>
    </form>
<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>