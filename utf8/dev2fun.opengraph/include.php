<?php
/**
 * @author dev2fun <darkfriend>
 * @copyright (c) 2017, darkfriend <hi@darkfriend.ru>
 * @version 1.1.0
 */
IncludeModuleLangFile(__FILE__);

\Bitrix\Main\Loader::registerAutoLoadClasses(
    "dev2fun.opengraph",
    array(
        'Dev2fun\\OpenGraph\\OpenGraphTable' => 'classes/general/OpenGraphTable.php',
        'Dev2fun\\Module\\OpenGraph' => 'classes/general/OpenGraph.php',
        'dev2funModuleOpenGraphClass' => 'include.php',
    )
);

if(class_exists('dev2funModuleOpenGraphClass')) return;

class dev2funModuleOpenGraphClass {

    private static $instance;
    public static $module_id = 'dev2fun.opengraph';
    public static $filedsName = 'dev2fun_og_fields';
    public static $settingsFieldName = 'dev2fun_og_fields_setting';
    const SETTINGS_EXCLUDED_NAME = 'dev2fun_og_excluded_page';
    const SETTINGS_RESIZE_IMAGE_NAME = 'RESIZE_IMAGE';
    const SETTINGS_SORTABLE_NAME = 'SORTABLE';

    /**
     * Open Graph require params
     * @var array
     */
    public static $arReqOG = array(
        'title',
        'description',
        'url',
        'type',
        'site_name',
    );

    /**
     * Default fields values for settings
     * @var array
     */
    public static $arReqSettings = array(
        'CACHE_TIME' => 3600
    );

    /**
     * Singleton instance.
     * @return dev2funModuleOpenGraphClass
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new dev2funModuleOpenGraphClass();
        }
        return self::$instance;
    }

    /**
     * Get fields for open graph
     * @param bool $incDefault include require fields (false)
     * @return array
     */
    public static function getFields($incDefault=false){
        $ogFields = COption::GetOptionString( dev2funModuleOpenGraphClass::$module_id, self::$filedsName);
        if($ogFields){
            $ogFields = unserialize($ogFields);
        } else {
            $ogFields = array();
        }
        if($incDefault){
            $ogFields = array_merge(self::$arReqOG,$ogFields);
            $ogFields = array_unique($ogFields);
        }
        return $ogFields;
    }

    /**
     * Set fields open graph
     * @param array $ogFields - fields
     * @return bool
     */
    public static function setFields($ogFields){
        if(!$ogFields) return false;
        foreach ($ogFields as $key=>$field) {
            if(empty($field)){
                unset($ogFields[$key]);
            }
        }
        return COption::SetOptionString( dev2funModuleOpenGraphClass::$module_id, self::$filedsName, serialize($ogFields));
    }

    /**
     * Get fields setting for open graph
     * @param bool $incDefault
     * @return array
     */
    public static function getSettingFields($incDefault=true) {
        $sFields = COption::GetOptionString( self::$module_id, self::$settingsFieldName);
        if($sFields){
            $sFields = unserialize($sFields);
        } else {
            $sFields = array();
        }
        if($incDefault){
            $sFields = array_merge(self::$arReqSettings,$sFields);
        }
        return $sFields;
    }

    /**
     * Set fields settings
     * @param array $sFields - fields
     * @return bool
     */
    public static function setSettingFields($sFields) {
        if(!$sFields) return false;
        foreach ($sFields as $key=>$field) {
            if(empty($field)){
                unset($sFields[$key]);
            }
        }
        return COption::SetOptionString( self::$module_id, self::$settingsFieldName, serialize($sFields));
    }

    /**
     * Get excluded pages
     * @return array
     */
    public function getSettingsExcludePage() {
        $pages = COption::GetOptionString( self::$module_id, self::SETTINGS_EXCLUDED_NAME);
        if($pages){
            $pages = unserialize($pages);
        } else {
            $pages = array();
        }
        return $pages;
    }

    /**
     * Set excluded pages
     * @param array $sFields
     * @return bool
     */
    public function setSettingsExcludePage($sFields) {
        if(!$sFields) return false;
        foreach ($sFields as $key=>$field) {
            if(empty($field)){
                unset($sFields[$key]);
            }
        }
        return COption::SetOptionString( self::$module_id, self::SETTINGS_EXCLUDED_NAME, serialize($sFields));
    }

    /**
     * Get settings resize image
     * @return array
     */
    public function getSettingsResize() {
        $data = COption::GetOptionString( self::$module_id, self::SETTINGS_RESIZE_IMAGE_NAME);
        if($data){
            $data = unserialize($data);
        } else {
            $data = array();
        }
        return $data;
    }

    /**
     * Get settings sortable logic
     * @return array
     */
    public function getSettingsSortable() {
        $data = COption::GetOptionString( self::$module_id, self::SETTINGS_SORTABLE_NAME);
        if($data){
            $data = unserialize($data);
        } else {
            $data = array();
        }
        return $data;
    }

    function AddOpenGraph() {
        global $APPLICATION;
        $curPage = $APPLICATION->GetCurPage();
        if(preg_match('#\/bitrix\/#',$curPage)) return;
        $obCache = new CPHPCache;
        $arSettings = self::getSettingFields();
        $cache_id = md5($APPLICATION->GetCurPage());
        foreach (GetModuleEvents(self::$module_id, "OnBeforeAddOpenGraph", true) as $arEvent)
            ExecuteModuleEventEx($arEvent, array(&$arSettings,&$cache_id));
        if($arSettings) {
            $life_time = $arSettings['CACHE_TIME'];
        }
        $og = \Dev2fun\Module\OpenGraph::getInstance();
        if($obCache->InitCache($life_time, $cache_id)){
            $arData = $obCache->GetVars();
        } elseif($obCache->StartDataCache()) {
            $oModule = self::getInstance();

            $arExcluded = $oModule->getSettingsExcludePage();
            if($curPage=='/') $curPage = 'index';
            if($arExcluded && in_array(ltrim($curPage,'/'),$arExcluded)) {
                $obCache->EndDataCache(0);
                return;
            }
			if($arExcluded) {
				foreach($arExcluded as $exc){
					if(preg_match($exc,$curPage)){
						$obCache->EndDataCache(0);
						return;
					}
				}
			}

            $og->ogFields = self::getFields(true);
            foreach ($og->ogFields as $reqItem) {
                $og->ogValues[$reqItem] = '';
            }
            $arSort = $oModule->getSettingsSortable();
            foreach ($arSort as $sort) {
                switch ($sort) {
                    case 'og_fields' :
                        if($og->refId && $og->refType)
                            $og->ogValues = $og->setPropertyOpenGraphFields($og->ogValues,$og->refId,$og->refType);
                        foreach (GetModuleEvents(self::$module_id, "OnAfterAddOgFields", true) as $arEvent)
                            ExecuteModuleEventEx($arEvent, array(&$og->ogValues));
                        break;
                    case 'iblock_fields' :
                        if($og->refId && $og->refType)
                            $og->ogValues = $og->setPropertyIBlockFields($og->ogValues,$og->refId,$og->refType);
                        foreach (GetModuleEvents(self::$module_id, "OnAfterAddIBlockFields", true) as $arEvent)
                            ExecuteModuleEventEx($arEvent, array(&$og->ogValues));
                        break;
                    case 'prop_fields' :
                        $og->ogValues = $og->setPropertyPropFields($og->ogValues);
                        foreach (GetModuleEvents(self::$module_id, "OnAfterAddPropFields", true) as $arEvent)
                            ExecuteModuleEventEx($arEvent, array(&$og->ogValues));
                        break;
                    case 'default' :
                        $og->ogValues = $og->setPropertyDefault($og->ogValues);
                        foreach (GetModuleEvents(self::$module_id, "OnAfterAddDefault", true) as $arEvent)
                            ExecuteModuleEventEx($arEvent, array(&$og->ogValues));
                        break;
                }
            }
            foreach (GetModuleEvents(self::$module_id, "OnAfterAdd", true) as $arEvent)
                ExecuteModuleEventEx($arEvent, array(&$og->ogValues));
            $arData = $og->ogValues;
            $obCache->EndDataCache($og->ogValues);
        }
        foreach (GetModuleEvents(self::$module_id, "OnBeforeOutput", true) as $arEvent)
            ExecuteModuleEventEx($arEvent, array(&$arData));
        $arStr = $og->getMeta($arData);
        if(empty($arStr)) return;
        $og->addHeader($arStr);
    }

    /**
     * Get protocol
     * @return string
     */
    public function getProtocol() {
        $protocol = 'http';
        if(CMain::IsHTTPS()) {
            $protocol .= 's';
        }
        return ($protocol.'://');
    }

    /**
     * Get domain
     * @return mixed
     */
    public function getHost() {
        $host = SITE_SERVER_NAME;
        if(!$host) {
            $host = $_SERVER['HTTP_HOST'];
        }
        return $host;
    }

    /**
     * Get url
     * @param string $path
     * @return bool|string
     */
    public function getUrl($path='') {
        if(!$path) return false;
        $beforeUrl = $this->getProtocol().$this->getHost();
        if(!preg_match('#('.addslashes($beforeUrl).')#',$path)) {
            $path = $this->getProtocol().$this->getHost().$path;
        }
        return $path;
    }

	public static function ShowThanksNotice() {
    	global $APPLICATION;
		\CAdminNotify::Add([
			'MESSAGE' => \Bitrix\Main\Localization\Loc::getMessage('D2F_OPENGRAPH_DONATE_MESSAGE',['#URL#'=>$APPLICATION->GetCurUri('tabControl_active_tab=donate')]),
			'TAG' => 'dev2fun_opengraph_update',
			'MODULE_ID' => 'dev2fun.opengraph',
		]);
	}

    public function DoBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu) {
        $aModuleMenu[] = array(
            "parent_menu" => "global_menu_settings",
            "icon" => "dev2fun_admin_icon",
            "page_icon" => "dev2fun_admin_icon",
            "sort" => "900",
            "text" => GetMessage("MENU_TEXT"),
            "title" => GetMessage("MENU_TITLE"),
            "url" => "/bitrix/admin/dev2fun_opengraph_manager.php?action=settings",
            "items_id" => "menu_dev2fun_opengraph",
            "section" => "dev2fun_opengraph",
            "more_url" => array(),
            // "items" => array(
            //     array(
            //         "text" => GetMessage("SUB_SETINGS_MENU_TEXT"),
            //         "title" => GetMessage("SUB_SETINGS_MENU_TITLE"),
            //         "url" => "/bitrix/admin/dev2fun_opengraph_manager.php?action=settings",
            //         "sort" => "100",
            //         "icon" => "sys_menu_icon",
            //         "page_icon" => "default_page_icon",
            //     ),
            // )
        );
    }
}