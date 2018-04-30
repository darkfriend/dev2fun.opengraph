<?php
/**
 * @author dev2fun <darkfriend>
 * @copyright (c) 2017, darkfriend <hi@darkfriend.ru>
 * @version 1.1.0
 */
if(!check_bitrix_sessid()) return;
IncludeModuleLangFile(__FILE__);

CModule::IncludeModule("main");

use \Bitrix\Main\Localization\Loc;

$admMsg = new CAdminMessage(false);
$admMsg->ShowMessage(array(
    "MESSAGE" => Loc::getMessage('D2F_OPENGRAPH_UNINSTALL_SUCCESS'),
    "TYPE" => "OK"
));
echo BeginNote();
echo Loc::getMessage("D2F_OPENGRAPH_UNINSTALL_LAST_MSG");
EndNote();