<?php
/**
 * Created by PhpStorm.
 * User: darkfriend <hi@darkfriend.ru>
 * Date: 22.01.2023
 * Time: 16:24
 */

/** @var CMain $APPLICATION */
/** @var CUser $USER */
/** @var CDatabase $DB */
/** @var CUpdater $updater */

include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

global $USER;
if (!is_object($USER) || !$USER->IsAdmin() || !check_bitrix_sessid()) {
    die();
}

$moduleID = 'dev2fun.opengraph';

\Bitrix\Main\Loader::includeModule($moduleID);

CopyDirFiles(
    "{$_SERVER["DOCUMENT_ROOT"]}/bitrix/modules/{$moduleID}/install/css",
    "{$_SERVER["DOCUMENT_ROOT"]}/bitrix/css/{$moduleID}",
    true,
    true
);

\dev2funModuleOpenGraphClass::ShowThanksNotice();

echo '1.4.4 - DONE'.PHP_EOL;