<?php
/**
 * Created by PhpStorm.
 * User: darkfriend <hi@darkfriend.ru>
 * Date: 30.04.2018
 * Time: 11:30
 */

include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule("main");
CModule::IncludeModule("dev2fun.opengraph");

IncludeModuleLangFile(__FILE__);

global $APPLICATION, $DB;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

$moduleID = 'dev2fun.opengraph';

if(!CopyDirFiles(__DIR__."/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$moduleID, true, true)){
	throw new Exception(Loc::getMessage("ERRORS_SAVE_FILE",array('#DIR#'=>__DIR__."/install/js")));
}

if(!CopyDirFiles(__DIR__."/install/css", $_SERVER["DOCUMENT_ROOT"]."/bitrix/css/".$moduleID, true, true)){
	throw new Exception(Loc::getMessage("ERRORS_SAVE_FILE",array('#DIR#'=>__DIR__."/install/css")));
}

$sortable = array(
	'og_fields',
	'iblock_fields',
	'prop_fields',
	'default',
);
Option::set($moduleID,'SORTABLE',serialize($sortable));

echo '1.2.0 - DONE'.PHP_EOL;