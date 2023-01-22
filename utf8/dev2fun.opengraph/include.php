<?php
/**
 * @author dev2fun <darkfriend>
 * @copyright (c) 2019-2023, darkfriend <hi@darkfriend.ru>
 * @version 1.4.2
 */
IncludeModuleLangFile(__FILE__);

\Bitrix\Main\Loader::registerAutoLoadClasses(
    "dev2fun.opengraph",
    array(
        'Dev2fun\\OpenGraph\\OpenGraphTable' => 'classes/general/OpenGraphTable.php',
        'Dev2fun\\Module\\OpenGraph' => 'classes/general/OpenGraph.php',
        'dev2funModuleOpenGraphClass' => 'include.php',

        'Dev2fun\\Module\\PageDataGraph' => 'classes/general/PageDataGraph.php',
        'Dev2fun\\Module\\PageGraph' => 'classes/general/PageGraph.php',
        'Dev2fun\\Module\\PagePathGraph' => 'classes/general/PagePathGraph.php',
    )
);

if (class_exists('dev2funModuleOpenGraphClass')) return;

use Bitrix\Main\Context;
use \Bitrix\Main\Localization\Loc;

class dev2funModuleOpenGraphClass
{
    private static $instance;
    public static $module_id = 'dev2fun.opengraph';
    public static $filedsName = 'dev2fun_og_fields';
    public static $fieldsAdditionalName = 'dev2fun_og_fields_additional';
    public static $settingsFieldName = 'dev2fun_og_fields_setting';
    const SETTINGS_EXCLUDED_NAME = 'dev2fun_og_excluded_page';
    const SETTINGS_RESIZE_IMAGE_NAME = 'RESIZE_IMAGE';
    const SETTINGS_SORTABLE_NAME = 'SORTABLE';
    public $httpHost;

    /**
     * Open Graph require params
     * @var array
     */
    public static $arReqOG = [
        'title',
        'description',
        'url',
        'type',
        'site_name',
        'image',
    ];

    /**
     * Default fields values for settings
     * @var array
     */
    public static $arReqSettings = [
        'CACHE_TIME' => 360000
    ];

    /**
     * Singleton instance.
     * @return dev2funModuleOpenGraphClass
     */
    public static function getInstance()
    {
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
    public static function getFields($incDefault = false)
    {
        $ogFields = COption::GetOptionString(dev2funModuleOpenGraphClass::$module_id, self::$filedsName);
        if ($ogFields) {
            $ogFields = unserialize($ogFields, ['allowed_classes' => false]);
        } else {
            $ogFields = [];
        }
        if ($incDefault) {
            $ogFields = array_merge(self::$arReqOG, $ogFields);
            $ogFields = array_unique($ogFields);
        }
        return $ogFields;
    }

    /**
     * Get additional fields for open graph
     * @return array
     */
    public static function getFieldsAdditional()
    {
        $ogFields = COption::GetOptionString(dev2funModuleOpenGraphClass::$module_id, self::$fieldsAdditionalName);
        if ($ogFields) {
            $ogFields = unserialize($ogFields, ['allowed_classes' => false]);
        } else {
            $ogFields = [];
        }
        return $ogFields;
    }


    /**
     * Set fields open graph
     * @param array $ogFields - fields
     * @return bool
     */
    public static function setFields($ogFields)
    {
        if (!$ogFields) return false;
        foreach ($ogFields as $key => $field) {
            if (empty($field)) {
                unset($ogFields[$key]);
            }
        }
        return COption::SetOptionString(dev2funModuleOpenGraphClass::$module_id, self::$filedsName, serialize($ogFields));
    }

    /**
     * Set additional fields open graph
     * @param array $ogFields - fields
     * @return bool
     */
    public static function setFieldsAdditional($ogFields)
    {
        if (!$ogFields) return false;
        foreach ($ogFields as $key => $field) {
            if (empty($field)) unset($ogFields[$key]);
        }
        return COption::SetOptionString(
            dev2funModuleOpenGraphClass::$module_id,
            self::$fieldsAdditionalName,
            serialize($ogFields)
        );
    }

    /**
     * Get fields setting for open graph
     * @param bool $incDefault
     * @return array
     */
    public static function getSettingFields($incDefault = true)
    {
        $sFields = COption::GetOptionString(self::$module_id, self::$settingsFieldName);
        if ($sFields) {
            $sFields = unserialize($sFields, ['allowed_classes' => false]);
        } else {
            $sFields = [];
        }
        if ($incDefault) {
            $sFields = array_merge(self::$arReqSettings, $sFields);
        }
        return $sFields;
    }

    /**
     * Get all settings for module
     * @return array
     */
    public static function getAllSettings()
    {
        $otherSettings = [
            'REMOVE_INDEX',
            'AUTO_ADD_TITLE',
            'AUTO_ADD_DESCRIPTION',
            'AUTO_ADD_IMAGE',
            'SHOW_IN_ELEMENTS',
            'SHOW_IN_SECTIONS',
        ];
        $resSettings = [];
        foreach ($otherSettings as $otherSetting) {
            $resSettings[$otherSetting] = COption::GetOptionString(self::$module_id, $otherSetting);
        }
        return $resSettings;
    }

    /**
     * Set fields settings
     * @param array $sFields - fields
     * @return bool
     */
    public static function setSettingFields($sFields)
    {
        if (!$sFields) return false;
        foreach ($sFields as $key => $field) {
            if (empty($field)) {
                unset($sFields[$key]);
            }
        }
        return COption::SetOptionString(self::$module_id, self::$settingsFieldName, serialize($sFields));
    }

    /**
     * Get excluded pages
     * @return array
     */
    public function getSettingsExcludePage()
    {
        $pages = COption::GetOptionString(self::$module_id, self::SETTINGS_EXCLUDED_NAME);
        if ($pages) {
            $pages = unserialize($pages, ['allowed_classes' => false]);
        } else {
            $pages = [];
        }
        return $pages;
    }

    /**
     * Set excluded pages
     * @param array $sFields
     * @return bool
     */
    public function setSettingsExcludePage($sFields)
    {
        if (!$sFields) return false;
        foreach ($sFields as $key => $field) {
            if (empty($field)) {
                unset($sFields[$key]);
            }
        }
        return COption::SetOptionString(self::$module_id, self::SETTINGS_EXCLUDED_NAME, serialize($sFields));
    }

    /**
     * Get settings resize image
     * @return array
     */
    public function getSettingsResize()
    {
        $data = COption::GetOptionString(self::$module_id, self::SETTINGS_RESIZE_IMAGE_NAME);
        if ($data) {
            $data = unserialize($data, ['allowed_classes' => false]);
        } else {
            $data = [];
        }
        return $data;
    }

    /**
     * Get settings sortable logic
     * @return array
     */
    public function getSettingsSortable()
    {
        $data = COption::GetOptionString(self::$module_id, self::SETTINGS_SORTABLE_NAME);
        if ($data) {
            $data = unserialize($data, ['allowed_classes' => false]);
        } else {
            $data = [];
        }
        return $data;
    }

    public static function AddOpenGraph()
    {
        global $APPLICATION, $USER;

        if (defined("ERROR_404") && ERROR_404 === "Y") return;
        if (preg_match('#(^4|3)#', http_response_code())) return;

        $curPage = $APPLICATION->GetCurPage();
        if (preg_match('#\/bitrix\/#', $curPage)) return;

        $obCache = new CPHPCache;
        $arSettings = self::getSettingFields();
        $domain = $_SERVER['HTTP_HOST'];
        if (!$domain) {
            $domain = Context::getCurrent()->getSite();
        }
        $cachePath = '/dev2fun.opengraph/' . $domain . '/';
        $cache_id = md5($domain . $curPage);

        foreach (GetModuleEvents(self::$module_id, "OnBeforeAddOpenGraph", true) as $arEvent) {
            ExecuteModuleEventEx($arEvent, array(&$arSettings, &$cache_id, &$cachePath));
        }

        if ($USER->IsAdmin() && !empty($_REQUEST['clear_cache'])) {
            $obCache->Clean($cache_id, $cachePath);
        }

        $og = \Dev2fun\Module\OpenGraph::getInstance();
        $life_time = $og->getOptionByParams('CACHE_TIME', 0);
        if(!$life_time) {
            $life_time = !empty($arSettings['CACHE_TIME']) ? $arSettings['CACHE_TIME'] : 3600;
        }

        if ($obCache->InitCache($life_time, $cache_id, $cachePath)) {
            $arData = $obCache->GetVars();
        } elseif ($obCache->StartDataCache()) {
            $oModule = self::getInstance();

            $arExcluded = $oModule->getSettingsExcludePage();
            if ($curPage === '/') $curPage = 'index';
            if ($arExcluded && in_array(ltrim($curPage, '/'), $arExcluded)) {
                $obCache->EndDataCache(0);
                return;
            }
            if ($arExcluded) {
                foreach ($arExcluded as $exc) {
                    if (preg_match($exc, $curPage)) {
                        $obCache->EndDataCache(0);
                        return;
                    }
                }
            }

            $og->settings = self::getAllSettings();
            $keyShowType = '';
            if ($og->refType === 'element') {
                $keyShowType = 'SHOW_IN_ELEMENTS';
            } elseif ($og->refType === 'section') {
                $keyShowType = 'SHOW_IN_SECTIONS';
            }
            if (isset($og->settings[$keyShowType]) && $og->settings[$keyShowType] !== 'Y') {
                $obCache->EndDataCache(0);
                return;
            }

            $og->ogFields = self::getFields(true);
            $og->baseOpenGraphFields = $og->ogFields;
            foreach ($og->ogFields as $key => $ogField) {
                $og->ogFields[$key] = 'og:' . $ogField;
            }

            $additionalFields = self::getFieldsAdditional();
            if ($additionalFields) {
                foreach ($additionalFields as $additionalField) {
                    $og->ogFields[] = $additionalField;
                }
                //				$og->ogFields = array_merge($og->ogFields,$additionalFields);
                //				foreach ($additionalFields as $additionalField) {
                //					if(empty($additionalField['key'])||empty($additionalField['value']))
                //						continue;
                //					$og->ogFields[$additionalField['key']] = $additionalField['value'];
                //				}
            }

            $og->ogFields = $og->prepareOpenGraphFields($og->ogFields);

            foreach ($og->ogFields as $reqItem) {
                $og->ogValues[$reqItem] = $og->getDefaultByField($reqItem, '');
            }

            $arSort = $oModule->getSettingsSortable();
            foreach ($arSort as $sort) {
                switch ($sort) {
                    case 'og_fields':
                        if ($og->refId && $og->refType)
                            $og->ogValues = $og->setPropertyOpenGraphFields($og->ogValues, $og->refId, $og->refType);
                        foreach (GetModuleEvents(self::$module_id, "OnAfterAddOgFields", true) as $arEvent) {
                            ExecuteModuleEventEx($arEvent, array(&$og->ogValues));
                        }
                        break;
                    case 'iblock_fields':
                        if ($og->refId && $og->refType)
                            $og->ogValues = $og->setPropertyIBlockFields($og->ogValues, $og->refId, $og->refType);
                        foreach (GetModuleEvents(self::$module_id, "OnAfterAddIBlockFields", true) as $arEvent) {
                            ExecuteModuleEventEx($arEvent, array(&$og->ogValues));
                        }
                        break;
                    case 'prop_fields':
                        $og->ogValues = $og->setPropertyPropFields($og->ogValues);
                        foreach (GetModuleEvents(self::$module_id, "OnAfterAddPropFields", true) as $arEvent) {
                            ExecuteModuleEventEx($arEvent, array(&$og->ogValues));
                        }
                        break;
                    case 'default':
                        $og->ogValues = $og->setPropertyDefault($og->ogValues);
                        foreach (GetModuleEvents(self::$module_id, "OnAfterAddDefault", true) as $arEvent) {
                            ExecuteModuleEventEx($arEvent, array(&$og->ogValues));
                        }
                        break;
                }
            }

            $og->ogValues = $og->prepareFieldsValues($og->ogValues);

            foreach (GetModuleEvents(self::$module_id, "OnAfterAdd", true) as $arEvent) {
                ExecuteModuleEventEx($arEvent, array(&$og->ogValues));
            }

            $arData = $og->ogValues;
            $obCache->EndDataCache($og->ogValues);
        }

        foreach (GetModuleEvents(self::$module_id, "OnBeforeOutput", true) as $arEvent) {
            ExecuteModuleEventEx($arEvent, array(&$arData));
        }

        $arStr = $og->getMeta($arData);
        if (empty($arStr)) return;
        $og->addHeader($arStr);
    }

    /**
     * Clear cache fot opengraph
     * @param boolean $all
     * @return mixed
     */
    public static function clearCache($all = false)
    {
        $cachePath = '/dev2fun.opengraph/';
        if (!$all) {
            $domain = $_SERVER['HTTP_HOST'];
            if (!$domain) {
                $domain = SITE_ID;
            }
            $cachePath .= $domain . '/';
        }
        return Bitrix\Main\Data\Cache::createInstance()->cleanDir($cachePath);
    }

    /**
     * Get protocol
     * @return string
     */
    public function getProtocol()
    {
        $protocol = 'http';
        if (CMain::IsHTTPS()) {
            $protocol .= 's';
        }
        return ($protocol . '://');
    }

    /**
     * Get domain
     * @return mixed
     */
    public function getHost()
    {
        if (!$this->httpHost)
            $this->httpHost = preg_replace('#(\:\d+)#', '', $_SERVER['HTTP_HOST']);
        return $this->httpHost;
    }

    /**
     * Get url
     * @param string $path
     * @return bool|string
     */
    public function getUrl($path = '')
    {
        if (!$path) return false;
        $beforeUrl = $this->getProtocol() . $this->getHost();
        if (!preg_match('#(' . addslashes($beforeUrl) . ')#', $path)) {
            $path = $this->getProtocol() . $this->getHost() . $path;
        }
        return $path;
    }

    public static function ShowThanksNotice()
    {
        \CAdminNotify::Add([
            'MESSAGE' => Loc::getMessage('D2F_OPENGRAPH_DONATE_MESSAGE', ['#URL#' => '/bitrix/admin/dev2fun_opengraph_manager.php?action=settings&tabControl_active_tab=donate']),
            'TAG' => 'dev2fun_opengraph_update',
            'MODULE_ID' => 'dev2fun.opengraph',
        ]);
    }

    public static function DoBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu)
    {
        $aModuleMenu[] = [
            "parent_menu" => "global_menu_settings",
            "icon" => "dev2fun_admin_icon",
            "page_icon" => "dev2fun_admin_icon",
            "sort" => "900",
            "text" => Loc::getMessage("MENU_TEXT"),
            "title" => Loc::getMessage("MENU_TITLE"),
            "url" => "/bitrix/admin/dev2fun_opengraph_manager.php?action=settings",
            "items_id" => "menu_dev2fun_opengraph",
            "section" => "dev2fun_opengraph",
            "more_url" => [],
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
        ];
    }
}