<?php
/**
 * Install
 * @author dev2fun (darkfriend)
 * @copyright (c) 2019-2023, darkfriend <hi@darkfriend.ru>
 * @version 1.4.2
 */
IncludeModuleLangFile(__FILE__);

\Bitrix\Main\Loader::registerAutoLoadClasses(
    "dev2fun.opengraph",
    [
        'Dev2fun\\OpenGraph\\OpenGraphTable' => 'classes/general/OpenGraphTable.php',
        'Dev2fun\\Module\\OpenGraph' => 'classes/general/OpenGraph.php',
        'dev2funModuleOpenGraphClass' => 'include.php',
    ]
);

if (class_exists("dev2fun_opengraph")) {
    return;
}

use Bitrix\Main\Localization\Loc,
    Dev2fun\OpenGraph\OpenGraphTable,
    Bitrix\Main\Config\Option;

class dev2fun_opengraph extends CModule
{
    var $MODULE_ID = "dev2fun.opengraph";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_GROUP_RIGHTS = "Y";

    public function __construct()
    {
        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path . "/version.php");
        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        } else {
            $this->MODULE_VERSION = '1.0.0';
            $this->MODULE_VERSION_DATE = '2016-10-16 15:00:00';
        }
        $this->MODULE_NAME = GetMessage("DEV2FUN_MODULE_NAME_OG");
        $this->MODULE_DESCRIPTION = GetMessage("DEV2FUN_MODULE_DESCRIPTION_OG");
        $this->PARTNER_NAME = "dev2fun";
        $this->PARTNER_URI = "http://dev2fun.com/";
    }

    public function DoInstall()
    {
        global $APPLICATION;
        if (!check_bitrix_sessid()) {
            return false;
        }
        try {
            $this->installFiles();
            $this->installDB();
            $this->registerEvents();
            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
            $APPLICATION->IncludeAdminFile(GetMessage("D2F_OPENGRAPH_STEP1"), __DIR__ . "/step1.php");
        } catch (Exception $e) {
            $APPLICATION->ThrowException($e->getMessage());
            return false;
        }
        return true;
    }

    public function installDB()
    {
        global $DB;
        try {
            $tableExist = $DB->Query('SELECT * FROM ' . OpenGraphTable::getTableName() . ' LIMIT 1', true);
            if (!$tableExist) {
                OpenGraphTable::getEntity()->createDbTable();
                $connection = \Bitrix\Main\Application::getInstance()->getConnection();
                $connection->createIndex(OpenGraphTable::getTableName(), 'IDX_REFERENCE_ID', 'REFERENCE_ID');
                $connection->createIndex(OpenGraphTable::getTableName(), 'IDX_META_KEY', 'META_KEY');
                $connection->createIndex(OpenGraphTable::getTableName(), 'IDX_REFERENCE_TYPE', 'REFERENCE_TYPE');
            }
            dev2funModuleOpenGraphClass::setFields(dev2funModuleOpenGraphClass::$arReqOG);
            Option::set($this->MODULE_ID, 'ADDTAB_ELEMENT', 'Y');
            Option::set($this->MODULE_ID, 'ADDTAB_SECTION', 'Y');
            Option::set($this->MODULE_ID, 'SHOW_IN_ELEMENTS', 'Y');
            Option::set($this->MODULE_ID, 'SHOW_IN_SECTIONS', 'Y');
        } catch (\Bitrix\Main\DB\SqlQueryException $e) {
            throw new Exception($e->getMessage());
        }
        return true;
    }

    public function registerEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();

        $eventManager->registerEventHandler("main", "OnPageStart", $this->MODULE_ID, "Dev2fun\\Module\\OpenGraph", "SetEventHandler");
        //        $eventManager->registerEventHandler("main", "OnEpilog", $this->MODULE_ID, "dev2funModuleOpenGraphClass", "AddOpenGraph");
        $eventManager->registerEventHandler("main", "OnBuildGlobalMenu", $this->MODULE_ID, "dev2funModuleOpenGraphClass", "DoBuildGlobalMenu");

        $eventManager->registerEventHandler("main", "OnAdminTabControlBegin", $this->MODULE_ID, "Dev2fun\\Module\\OpenGraph", "AddAdminTab");

        $eventManager->registerEventHandler("iblock", "OnBeforeIBlockElementUpdate", $this->MODULE_ID, "Dev2fun\\Module\\OpenGraph", "saveElement");
        $eventManager->registerEventHandler("iblock", "OnBeforeIBlockElementAdd", $this->MODULE_ID, "Dev2fun\\Module\\OpenGraph", "saveElement");

        $eventManager->registerEventHandler("iblock", "OnAfterIBlockElementDelete", $this->MODULE_ID, "Dev2fun\\Module\\OpenGraph", "deleteElement");


        $eventManager->registerEventHandler("iblock", "OnAfterIBlockSectionAdd", $this->MODULE_ID, "Dev2fun\\Module\\OpenGraph", "saveSection");
        $eventManager->registerEventHandler("iblock", "OnAfterIBlockSectionUpdate", $this->MODULE_ID, "Dev2fun\\Module\\OpenGraph", "saveSection");

        $eventManager->registerEventHandler("iblock", "OnAfterIBlockSectionDelete", $this->MODULE_ID, "Dev2fun\\Module\\OpenGraph", "deleteSection");

        return true;
    }

    public function installFiles()
    {
        // copy admin files
        if (!CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/admin", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin", true, true)) {
            throw new Exception(Loc::getMessage("ERRORS_CREATE_DIR", ['#DIR#' => 'bitrix/admin']));
        }

        // copy themes files
        if (!CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/themes", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/themes", true, true)) {
            throw new Exception(Loc::getMessage("ERRORS_CREATE_DIR", ['#DIR#' => 'bitrix/themes']));
        }

        // copy js files
        if (!CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/js", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/js/" . $this->MODULE_ID, true, true)) {
            throw new Exception(Loc::getMessage("ERRORS_CREATE_DIR", ['#DIR#' => 'bitrix/js']));
        }

        if (!CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/css", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/css/" . $this->MODULE_ID, true, true)) {
            throw new Exception(Loc::getMessage("ERRORS_CREATE_DIR", ['#DIR#' => __DIR__ . "/install/css"]));
            return false;
        }

        return true;
    }

    public function DoUninstall()
    {
        global $APPLICATION;
        if (!check_bitrix_sessid()) return false;
        try {
            $this->deleteFiles();
            $this->unInstallDB();
            $this->unRegisterEvents();
            \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
            $APPLICATION->IncludeAdminFile(Loc::getMessage("D2F_OPENGRAPH_UNSTEP1"), __DIR__ . "/unstep1.php");
        } catch (Exception $e) {
            $APPLICATION->ThrowException($e->getMessage());
            return false;
        }
        return true;
    }

    public function deleteFiles()
    {

        if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/bitrix/admin/dev2fun_opengraph_manager.php") && !DeleteDirFilesEx("/bitrix/admin/dev2fun_opengraph_manager.php")) {
            throw new Exception(Loc::getMessage("ERRORS_DELETE_FILE", ['#FILE#' => 'bitrix/admin/dev2fun_opengraph_manager.php']));
        }
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/themes/.default/icons/' . $this->MODULE_ID) && !DeleteDirFilesEx('/bitrix/themes/.default/icons/' . $this->MODULE_ID)) {
            throw new Exception(Loc::getMessage("ERRORS_DELETE_FILE", ['#FILE#' => 'bitrix/themes/.default/icons/' . $this->MODULE_ID]));
        }
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/themes/.default/' . $this->MODULE_ID . '.css') && !DeleteDirFilesEx('/bitrix/themes/.default/' . $this->MODULE_ID . '.css')) {
            throw new Exception(Loc::getMessage("ERRORS_DELETE_FILE", ['#FILE#' => 'bitrix/themes/.default/' . $this->MODULE_ID . '.css']));
        }
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/' . $this->MODULE_ID) && !DeleteDirFilesEx('/bitrix/js/' . $this->MODULE_ID)) {
            throw new Exception(Loc::getMessage("ERRORS_DELETE_FILE", ['#FILE#' => 'bitrix/js/' . $this->MODULE_ID]));
        }
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/css/' . $this->MODULE_ID) && !DeleteDirFilesEx('/bitrix/css/' . $this->MODULE_ID)) {
            throw new Exception(Loc::getMessage("ERRORS_DELETE_FILE", ['#FILE#' => 'bitrix/css/' . $this->MODULE_ID]));
        }
        return true;
    }

    public function unRegisterEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();

        //        $eventManager->unRegisterEventHandler('main','OnEpilog',$this->MODULE_ID);
        $eventManager->unRegisterEventHandler("main", "OnPageStart", $this->MODULE_ID);
        $eventManager->unRegisterEventHandler('main', 'OnBuildGlobalMenu', $this->MODULE_ID);
        $eventManager->unRegisterEventHandler('main', 'OnAdminTabControlBegin', $this->MODULE_ID);

        $eventManager->unRegisterEventHandler('iblock', 'OnAfterIBlockSectionUpdate', $this->MODULE_ID);
        $eventManager->unRegisterEventHandler('iblock', 'OnAfterIBlockSectionAdd', $this->MODULE_ID);
        $eventManager->unRegisterEventHandler('iblock', 'OnAfterIBlockElementDelete', $this->MODULE_ID);

        $eventManager->unRegisterEventHandler('iblock', 'OnAfterIBlockElementUpdate', $this->MODULE_ID);
        $eventManager->unRegisterEventHandler('iblock', 'OnAfterIBlockElementAdd', $this->MODULE_ID);
        $eventManager->unRegisterEventHandler('iblock', 'OnAfterIBlockSectionDelete', $this->MODULE_ID);

        return true;
    }

    public function unInstallDB()
    {
        global $DB, $DBType;
        $errors = $DB->RunSQLBatch(__DIR__ . "/db/uninstall.sql");
        if ($errors !== false) {
            throw new Exception(implode(PHP_EOL, $errors));
        }
        $connection = \Bitrix\Main\Application::getInstance()->getConnection();
        $connection->dropTable(OpenGraphTable::getTableName());
        \Bitrix\Main\Config\Option::delete($this->MODULE_ID);
        return true;
    }
}
