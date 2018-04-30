<?php
/** @var CMain $APPLICATION */
/** @var CUser $USER */
/** @var CDatabase $DB */
/** @var CUpdater $updater */
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
	return false;
}

if(!CopyDirFiles(__DIR__."/install/css", $_SERVER["DOCUMENT_ROOT"]."/bitrix/css/".$moduleID, true, true)){
	throw new Exception(Loc::getMessage("ERRORS_SAVE_FILE",array('#DIR#'=>__DIR__."/install/css")));
	return false;
}

$isSortable = Option::get($moduleID,'SORTABLE');
if(!$isSortable) {
	$sortable = array(
		'og_fields',
		'iblock_fields',
		'prop_fields',
		'default',
	);
	Option::set($moduleID,'SORTABLE',serialize($sortable));
}

$arRequireEvents = [
	'OnBuildGlobalMenu' => [
		'fromModuleId' => 'main',
		'toClass' => 'dev2funModuleOpenGraphClass',
		'toMethod' => 'DoBuildGlobalMenu',
	],

	'OnAdminTabControlBegin' => [
		'fromModuleId' => 'main',
		'toClass' => 'Dev2fun\Module\OpenGraph',
		'toMethod' => 'AddAdminTab',
	],

	'OnBeforeIBlockElementUpdate' => [
		'fromModuleId' => 'iblock',
		'toClass' => 'Dev2fun\Module\OpenGraph',
		'toMethod' => 'saveElement',
	],
	'OnBeforeIBlockElementAdd' => [
		'fromModuleId' => 'iblock',
		'toClass' => 'Dev2fun\Module\OpenGraph',
		'toMethod' => 'saveElement',
	],
	'OnAfterIBlockElementDelete' => [
		'fromModuleId' => 'iblock',
		'toClass' => 'Dev2fun\Module\OpenGraph',
		'toMethod' => 'deleteElement',
	],

	'OnAfterIBlockSectionAdd' => [
		'fromModuleId' => 'iblock',
		'toClass' => 'Dev2fun\Module\OpenGraph',
		'toMethod' => 'saveSection',
	],
	'OnAfterIBlockSectionUpdate' => [
		'fromModuleId' => 'iblock',
		'toClass' => 'Dev2fun\Module\OpenGraph',
		'toMethod' => 'saveSection',
	],
	'OnAfterIBlockSectionDelete' => [
		'fromModuleId' => 'iblock',
		'toClass' => 'Dev2fun\Module\OpenGraph',
		'toMethod' => 'deleteSection',
	],
];
$eventManager = \Bitrix\Main\EventManager::getInstance();
foreach ($arRequireEvents as $e => $arRequireEvent) {
	$events = $eventManager->findEventHandlers($arRequireEvent['fromModuleId'],$e);
	if($events) {
		$delEvents = [];
		foreach ($events as $event) {
			if($event['TO_MODULE_ID']==$moduleID && $event['TO_METHOD']==$event['toMethod']) {
				$delEvents[] = $event;
			}
		}
		if($delEvents) {
			foreach ($delEvents as $dEvent) {
				$eventManager->unRegisterEventHandler($dEvent['fromModuleId'],$e,$moduleID);
			}
		}
	} else {
		$eventManager->registerEventHandler($arRequireEvent['fromModuleId'], $e, $moduleID, $arRequireEvent['toClass'], $arRequireEvent['toMethod']);
	}
}
$eventManager->unRegisterEventHandler('main','OnEpilog',$moduleID,'dev2funModuleOpenGraphClass','AddOpenGraph');
$eventManager->unRegisterEventHandler('main','OnEpilog',$moduleID);

$eventManager->registerEventHandler(
	'main',
	'OnPageStart',
	$moduleID,
	'Dev2fun\\Module\\OpenGraph',
	'SetEventHandler'
);

echo '1.2.1 - DONE'.PHP_EOL;