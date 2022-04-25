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

$fields = dev2funModuleOpenGraphClass::getFields(true);

if(!in_array('image', $fields)) {
    $fields[] = 'image';
}

dev2funModuleOpenGraphClass::setFields($fields);

\dev2funModuleOpenGraphClass::ShowThanksNotice();

echo '1.4.0 - DONE'.PHP_EOL;