<?php
/**
 * Created by PhpStorm.
 * User: darkfriend <hi@darkfriend.ru>
 * Date: 15.12.2019
 * Time: 16:24
 */

/** @var CMain $APPLICATION */
/** @var CUser $USER */
/** @var CDatabase $DB */
/** @var CUpdater $updater */

include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$moduleID = 'dev2fun.opengraph';

\Bitrix\Main\Loader::includeModule($moduleID);
\Bitrix\Main\Config\Option::set($moduleID, 'SHOW_IN_ELEMENTS', 'Y');
\Bitrix\Main\Config\Option::set($moduleID, 'SHOW_IN_SECTIONS', 'Y');

\dev2funModuleOpenGraphClass::ShowThanksNotice();

echo '1.3.7 - DONE'.PHP_EOL;