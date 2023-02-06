<?php
/**
 * @author dev2fun <darkfriend>
 * @copyright (c) 2019-2023, darkfriend <hi@darkfriend.ru>
 * @version 1.4.3
 */

namespace Dev2fun\Module;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Dev2fun\OpenGraph\OpenGraphTable;

IncludeModuleLangFile(__FILE__);

class OpenGraph
{
    public $lastError;
    private static $instance;
    private static $_type;
    public static $_init;
    public $baseOpenGraphFields;
    public $ogFields;
    public $ogValues;
    public $ogOnProperty;
    public $params;

    /**
     * @var int reference id (element id or section id)
     */
    public $refId;
    /**
     * @var string reference type (element||section)
     */
    public $refType;

    public $settings;

    /**
     * Singleton instance.
     * @return self
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function IsAddTab()
    {
        global $APPLICATION;
        $curPath = $APPLICATION->GetCurPage();
        switch ($curPath) {
            case (preg_match('#(iblock_element_edit)#', $curPath) == 1) :
                $enableTabElement = Option::get(\dev2funModuleOpenGraphClass::$module_id, 'ADDTAB_ELEMENT', 'N');
                self::$_type = 'element';
                return ($enableTabElement === 'Y');
            case (preg_match('#(iblock_section_edit)#', $curPath) == 1) :
                $enableTabSection = Option::get(\dev2funModuleOpenGraphClass::$module_id, 'ADDTAB_SECTION', 'N');
                self::$_type = 'section';
                return ($enableTabSection === 'Y');
        }
        return false;
    }

    /**
     * @param \CAdminTabControl $form
     */
    public static function AddAdminTab(&$form)
    {
        Loader::includeModule("dev2fun.opengraph");
        if (!OpenGraph::$_init && self::IsAddTab()) {
            OpenGraph::$_init = true;
            $sTableID = 'opengraph_edition';
            Loader::includeModule("iblock");

            $module = \dev2funModuleOpenGraphClass::getInstance();
            $arFields = \dev2funModuleOpenGraphClass::getFields(true);
            $arFieldsAdditional = \dev2funModuleOpenGraphClass::getFieldsAdditional();
            $arOpenGraph = OpenGraph::getInstance()->getByRef($_REQUEST['ID'], self::$_type);

            ob_start();
            include_once __DIR__ . '/../../lib/views/admin.php';
            include_once __DIR__ . '/../../lib/views/admin_additional.php';
            $admLIST = ob_get_contents();
            ob_end_clean();

            $form->tabs[] = [
                "DIV" => "dev2fun_edition_list",
                "TAB" => Loc::getMessage('DEV2FUN_OG_TAB_NAME'),
                "ICON" => "main_user_edit",
                "TITLE" => Loc::getMessage('DEV2FUN_OG_TAB_TITLE'),
                "CONTENT" => '<tr><td colspan="2">' . $admLIST . '</td></tr>',
            ];
        }
    }

    /**
     * Event Handler on save element
     * @param array &$arFields
     */
    public static function saveElement(&$arFields)
    {
        if (!empty($arFields["ID"])) {
            $obParser = new \CTextParser;
            $ogFields = \dev2funModuleOpenGraphClass::getFields(true);
            $arSettings = \dev2funModuleOpenGraphClass::getAllSettings();
            $reqFields = $_REQUEST['DEV2FUN_OPENGRAPH'];
            if (
                isset($reqFields['title'])
                && empty($reqFields['title'])
                && !empty($arFields['NAME'])
                && (!empty($arSettings['AUTO_ADD_TITLE']) && $arSettings['AUTO_ADD_TITLE'] === 'Y')
            ) {
                $reqFields['title'] = $arFields['NAME'];
            }
            if (
                isset($reqFields['description'])
                && empty($reqFields['description'])
                && !empty($arFields['PREVIEW_TEXT'])
                && (!empty($arSettings['AUTO_ADD_DESCRIPTION']) && $arSettings['AUTO_ADD_DESCRIPTION'] === 'Y')
            ) {
                $reqFields['description'] = strip_tags($arFields['PREVIEW_TEXT']);
            } elseif (
                isset($reqFields['description'])
                && empty($reqFields['description'])
                && !empty($arFields['DETAIL_TEXT'])
                && (!empty($arSettings['AUTO_ADD_DESCRIPTION']) && $arSettings['AUTO_ADD_DESCRIPTION'] === 'Y')
            ) {
                $reqFields['description'] = strip_tags($arFields['DETAIL_TEXT']);
            }
            if (!empty($reqFields['description'])) {
                $reqFields['description'] = preg_replace("#('|\"|\r?\n)#", ' ', $reqFields['description']);
                $reqFields['description'] = $obParser->html_cut($reqFields['description'], 121);
            }

            if (in_array('image', $ogFields)) {
                $file = [];
                if (!empty($_POST['OG_IMAGE'])) {
                    $file = $_POST['OG_IMAGE'];
                    if (!empty($_POST['OG_IMAGE_del']) && $_POST['OG_IMAGE_del'] === 'Y') {
                        OpenGraph::getInstance()->deleteImage($file);
                        $file = 0;
                        $reqFields['image'] = '';
                    }
                    //                    $upload_dir = \COption::GetOptionString("main", "upload_dir", "upload");
                    //                    $absPath = $_SERVER["DOCUMENT_ROOT"]."/".$upload_dir."/tmp";
                    //                    if(!empty($_POST['OG_IMAGE']['tmp_name'])) {
                    //                        $_POST['OG_IMAGE']['tmp_name'] = $absPath.$_POST['OG_IMAGE']['tmp_name'];
                    //                    }
                    //                    $fileID = \CFile::SaveFile($_POST['OG_IMAGE'],'dev2fun_opengraph', true);
                    //                    if($fileID) $reqFields['image'] = $fileID;
                } elseif (
                    !empty($arFields['PREVIEW_PICTURE_ID'])
                    && (!empty($arSettings['AUTO_ADD_IMAGE']) && $arSettings['AUTO_ADD_IMAGE'] === 'Y')
                ) {
                    $file = $arFields['PREVIEW_PICTURE_ID'];
                    if (is_numeric($file)) {
                        $file = \CFile::MakeFileArray($file);
                    }
                } elseif (
                    !empty($_POST['PREVIEW_PICTURE'])
                    && (!empty($arSettings['AUTO_ADD_IMAGE']) && $arSettings['AUTO_ADD_IMAGE'] === 'Y')
                ) {
                    $file = $_POST['PREVIEW_PICTURE'];
                    if (is_numeric($file)) {
                        $file = \CFile::MakeFileArray($file);
                    }
                } elseif (
                    !empty($arFields['DETAIL_PICTURE_ID'])
                    && (!empty($arSettings['AUTO_ADD_IMAGE']) && $arSettings['AUTO_ADD_IMAGE'] === 'Y')
                ) {
                    $file = $arFields['DETAIL_PICTURE_ID'];
                    if (is_numeric($file)) {
                        $file = \CFile::MakeFileArray($file);
                    }
                } elseif (
                    !empty($_POST['DETAIL_PICTURE'])
                    && (!empty($arSettings['AUTO_ADD_IMAGE']) && $arSettings['AUTO_ADD_IMAGE'] === 'Y')
                ) {
                    $file = $_POST['DETAIL_PICTURE'];
                    if (is_numeric($file)) {
                        $file = \CFile::MakeFileArray($file);
                    }
                }

                if ($file) {
                    if (is_numeric($file)) {
                        $reqFields['image'] = $file;
                    } else {
                        if (empty($file['tmp_name'])) {
                            $file = \CFile::MakeFileArray($file);
                        } elseif (!file_exists($file['tmp_name'])) {
                            $upload_dir = \COption::GetOptionString("main", "upload_dir", "upload");
                            $absPath = $_SERVER["DOCUMENT_ROOT"] . "/" . $upload_dir . "/tmp";
                            if (!empty($file['tmp_name']) && !strpos($file['tmp_name'], $absPath)) {
                                $file['tmp_name'] = $absPath . $file['tmp_name'];
                            }
                        }
                        $fileID = \CFile::SaveFile($file, 'dev2fun_opengraph', true);
                        if ($fileID) $reqFields['image'] = $fileID;
                    }
                }
            }
            if ($reqFields) {
                OpenGraph::getInstance()->save($arFields["ID"], 'element', $reqFields);
            }
        }
    }

    public function deleteImage($id)
    {
        \CFile::Delete($id);
    }

    /**
     * Event Handler on save section
     * @param array &$arFields
     */
    public static function saveSection(&$arFields)
    {
        if (!empty($arFields["ID"])) {
            $obParser = new \CTextParser;
            $ogFields = \dev2funModuleOpenGraphClass::getFields(true);
            $arSettings = \dev2funModuleOpenGraphClass::getAllSettings();
            $reqFields = $_REQUEST['DEV2FUN_OPENGRAPH'];
            if (
                isset($reqFields['title'])
                && empty($reqFields['title'])
                && !empty($arFields['NAME'])
                && (!empty($arSettings['AUTO_ADD_TITLE']) && $arSettings['AUTO_ADD_TITLE'] === 'Y')
            ) {
                $reqFields['title'] = $arFields['NAME'];
            }
            if (
                isset($reqFields['description'])
                && empty($reqFields['description'])
                && !empty($arFields['DESCRIPTION'])
                && (!empty($arSettings['AUTO_ADD_DESCRIPTION']) && $arSettings['AUTO_ADD_DESCRIPTION'] === 'Y')
            ) {
                $reqFields['description'] = strip_tags($arFields['DESCRIPTION']);
                $reqFields['description'] = preg_replace("#('|\"|\r?\n)#", ' ', $reqFields['description']);
                $reqFields['description'] = $obParser->html_cut($reqFields['description'], 121);
            }
            if (in_array('image', $ogFields)) {
                $file = [];
                if (!empty($_POST['OG_IMAGE'])) {
                    $file = $_POST['OG_IMAGE'];
                    if (!empty($_POST['OG_IMAGE_del']) && $_POST['OG_IMAGE_del'] === 'Y') {
                        OpenGraph::getInstance()->deleteImage($file);
                        $file = 0;
                        $reqFields['image'] = '';
                    }
                } elseif (
                    !empty($_POST['PICTURE'])
                    && (!empty($arSettings['AUTO_ADD_IMAGE']) && $arSettings['AUTO_ADD_IMAGE'] === 'Y')
                ) {
                    $file = $_POST['PICTURE'];
                    if (is_numeric($file)) {
                        $file = \CFile::MakeFileArray($file);
                    }
                }
                if ($file) {
                    if (is_numeric($file)) {
                        $reqFields['image'] = $file;
                    } else {
                        if (empty($file['tmp_name'])) {
                            $file = \CFile::MakeFileArray($file);
                        } elseif (!file_exists($file['tmp_name'])) {
                            $upload_dir = \COption::GetOptionString("main", "upload_dir", "upload");
                            $absPath = $_SERVER["DOCUMENT_ROOT"] . "/" . $upload_dir . "/tmp";
                            if (!empty($file['tmp_name']) && !strpos($file['tmp_name'], $absPath)) {
                                $file['tmp_name'] = $absPath . $file['tmp_name'];
                            }
                        }
                        $fileID = \CFile::SaveFile($file, 'dev2fun_opengraph', true);
                        if ($fileID) $reqFields['image'] = $fileID;
                    }
                }
            }
            if ($reqFields) {
                OpenGraph::getInstance()->save($arFields["ID"], 'section', $reqFields);
            }
        }
    }

    /**
     * Save data in DB
     * @param integer $refId - reference id (element id OR section id)
     * @param string $type - reference type (element||section)
     * @param array $fields - metaKey=>metaVal
     * @return bool
     */
    public function save($refId, $type, $fields)
    {
        if (!$refId || !$type || !$fields) return false;
        global $APPLICATION;
        $arRows = OpenGraphTable::getList(['filter' => [
            'REFERENCE_ID' => $refId,
            'REFERENCE_TYPE' => $type,
        ]]);
        if ($arRows) {
            foreach ($arRows as $arRow) {
                if ($arRow['META_KEY'] === 'image' && empty($fields['image'])) {
                    $fields['image'] = $arRow['META_VAL'];
                }
                OpenGraphTable::delete($arRow['ID']);
            }
        }
        foreach ($fields as $metaKey => $metaVal) {
            $metaKey = htmlspecialcharsbx($metaKey);
            $metaVal = htmlspecialcharsbx($metaVal);
            $rowFields = [
                'REFERENCE_ID' => $refId,
                'REFERENCE_TYPE' => $type,
                'META_KEY' => $metaKey,
                'META_VAL' => $metaVal,
            ];
            $res = OpenGraphTable::add($rowFields);
            if ($res->isSuccess()) {
                continue;
            } else {
                $arErrors = $res->getErrorMessages();
                $this->lastError = $res->getErrorMessages();
                $APPLICATION->ThrowException(implode(PHP_EOL, $arErrors));
                return false;
            }
        }
        return true;
    }

    /**
     * Get Open Graph fields by reference id and reference type
     * @param integer $refId - reference id (element id OR section id)
     * @param string $type - reference type (element||section)
     * @return array - FIELD=>VALUE
     */
    public function getByRef($refId, $type)
    {
        $res = $this->getList([
            'REFERENCE_ID' => $refId,
            'REFERENCE_TYPE' => $type,
        ]);
        if ($res)
            return $res[$refId];
        return [];
    }

    /**
     * Get list
     * @param array $filter - filters
     * @param array $fields - select fields
     * @param array $sort - order and sort (fields=>ACS|DESC)
     * @return array - [REFERENCE_ID => [ FIELD=>VALUE,FIELD=>VALUE... ]...]
     */
    public function getList($filter = [], $fields = [], $sort = [])
    {
        $result = [];
        $res = $this->getQuery($filter, $fields, $sort);
        if ($res) {
            foreach ($res as $item) {
                $result[$item['REFERENCE_ID']][$item['META_KEY']] = $item['META_VAL'];
            }
        }
        return $result;
    }

    /**
     * Get result from database
     * @param array $filter - filters
     * @param array $fields - select fields
     * @param array $sort - order and sort (fields=>ACS|DESC)
     * @return \Bitrix\Main\DB\Result
     */
    public function getQuery($filter = [], $fields = [], $sort = [])
    {
        $query = [];
        if ($filter)
            $query['filter'] = $filter;
        if ($fields)
            $query['select'] = $fields;
        if ($sort)
            $query['order'] = $sort;
        return OpenGraphTable::getList($query);
    }

    /**
     * Output Open Graph meta tags
     * @param integer $refId - reference id (element id or section id)
     * @param string $type - reference type (element||section)
     * @param array $params - дополнительные параметры
     * [
     *    'ogOnProperty' => [
     *        'title' => 'default',
     *    ],
     *    'default' => [
     *            'fieldName' => 'value',
     *        ],
     * ]<br>
     * ogOnProperty - для того если допустим title установить только на определенном шаге. (og_fields,iblock_fields,prop_fields,default)
     *        title => default - для того чтоб задать заголовок по умолчанию
     * default - задает сразу значения по умолчанию для указанных ключей
     * @return bool
     */
    public static function Show($refId, $type = 'element', $params = [])
    {
        if (!$refId) return false;
        $og = OpenGraph::getInstance();
        $og->refId = $refId;
        $og->refType = $type;
        if (!empty($params['ogOnProperty']))
            $og->ogOnProperty = $params['ogOnProperty'];
        $og->setParams($params);
        return true;
    }

    public static function SetEventHandler()
    {
        AddEventHandler("main", "OnEpilog", ['dev2funModuleOpenGraphClass', 'AddOpenGraph'], 999999999);
    }

    /**
     * Возвращает путь до картинки.
     * Ресайзит картинку, если нужно
     * @param int $imageId
     * @return string
     */
    public function getImagePathById($imageId)
    {
        $settingsResize = \dev2funModuleOpenGraphClass::getInstance()->getSettingsResize();
        $imagePath = '';
        if ($settingsResize['ENABLE'] === 'Y' && (!empty($settingsResize['WIDTH']) || !empty($settingsResize['HEIGHT']))) {
            if (empty($settingsResize['TYPE'])) $settingsResize['TYPE'] = BX_RESIZE_IMAGE_PROPORTIONAL;
            $arImage = \CFile::ResizeImageGet($imageId, [
                'width' => (!empty($settingsResize['WIDTH']) ? $settingsResize['WIDTH'] : 99999),
                'height' => (!empty($settingsResize['HEIGHT']) ? $settingsResize['HEIGHT'] : 99999),
            ], $settingsResize['TYPE']);
            if ($arImage) {
                $imagePath = $arImage['src'];
            }
        }
        if (!$imagePath) {
            $imagePath = \CFile::GetPath($imageId);
        }
        if ($imagePath) {
            $oModule = \dev2funModuleOpenGraphClass::getInstance();
            $prefix = '';
            if (!preg_match('#^(http|https)#', $imagePath)) {
                $prefix = $oModule->getProtocol() . $oModule->getHost();
            }
            $imagePath = $prefix . $imagePath;
        }
        return $imagePath;
    }

    /**
     * Устанавливает ключи и значение из полей OpenGraph,
     * которые заполняются в element/section
     * @param array $ogData - массив из OpenGraph ключ=>значение
     * @param int $refId - id элемента/раздела
     * @param string $type - тип element/section
     * @return array
     */
    public function setPropertyOpenGraphFields($ogData, $refId, $type)
    {
        $data = $this->getByRef($refId, $type);
        if ($data) {
            foreach ($data as $ogKey => $ogVal) {
                if (in_array($ogKey, $this->baseOpenGraphFields)) $ogKey = 'og:' . $ogKey;
                if (empty($ogData[$ogKey]) && $this->checkOnProperty('og_fields', $ogKey)) {
                    switch ($ogKey) {
                        //                        case 'og:title' :
                        //                            if ($ogVal) {
                        //                                $ogVal = htmlentities($ogVal);
                        //                            }
                        //                            break;
                        //                        case 'og:description' :
                        //                            if ($ogVal) {
                        //                                $ogVal = trim(strip_tags(html_entity_decode($ogVal)));
                        //                                if (strlen($ogVal) > 160) {
                        //                                    $ogVal = substr($ogVal, 0, 160) . '...';
                        //                                }
                        //                                $ogVal = htmlentities($ogVal);
                        //                            }
                        //                            break;
                        case 'og:image' :
                            if ($ogVal) {
                                $ogVal = $this->getImagePathById($ogVal);
                            }
                            break;
                    }
                    $ogData[$ogKey] = $ogVal;
                }
            }
        }
        return $ogData;
    }

    /**
     * Проверка на право установки значения шагом
     * @param string $keyStep - кодовое название шага
     * @param string $ogKey - ключ OpenGraph поля
     * @return bool
     */
    public function checkOnProperty($keyStep, $ogKey)
    {
        if (empty($this->ogOnProperty)) return true;
        if (empty($this->ogOnProperty[$ogKey])) return true;
        if ($this->ogOnProperty[$ogKey] == $keyStep) return true;
        return false;
    }

    /**
     * Устанавливает ключи и значение из полей инфоблока,
     * которые заполняются в element/section
     * @param array $ogData - массив из OpenGraph ключ=>значение
     * @param int $refId - id элемента/раздела
     * @param string $type - тип element/section
     * @return array
     */
    public function setPropertyIBlockFields($ogData, $refId, $type)
    {
        $ogReqData = [];
        foreach ($this->ogFields as $k => $reqKey) {
            if (empty($ogData[$reqKey])) {
                $ogReqData[] = $reqKey;
            }
        }
        if (!$ogReqData) return $ogData;
        $oModule = \dev2funModuleOpenGraphClass::getInstance();
        if ($type === 'element') {
            $arElement = \CIBlockElement::GetByID($refId)->GetNext();
            if ($arElement) {
                foreach ($ogReqData as $reqKey) {
                    if (!$this->checkOnProperty('iblock_fields', $reqKey)) {
                        continue;
                    }
                    switch ($reqKey) {
                        case 'og:title' :
                            if (!empty($arElement['NAME'])) {
                                $ogData[$reqKey] = $arElement['NAME'];
                                //                                $ogData[$reqKey] = htmlentities($ogData[$reqKey]);
                            }
                            break;
                        case 'og:description' :
                            if (!empty($arElement['DETAIL_TEXT'])) {
                                $ogData[$reqKey] = $arElement['DETAIL_TEXT'];
                            } elseif (!empty($arElement['PREVIEW_TEXT'])) {
                                $ogData[$reqKey] = $arElement['PREVIEW_TEXT'];
                            }

                            //                            if (!empty($arElement['DETAIL_TEXT'])) {
                            //                                $text = strip_tags($arElement['DETAIL_TEXT']);
                            //                                if (strlen($text) > 160) {
                            //                                    $text = substr($text, 0, 160) . '...';
                            //                                }
                            //                                $text = htmlentities($text);
                            //                                $ogData[$reqKey] = $text;
                            //                            } elseif (!empty($arElement['PREVIEW_TEXT'])) {
                            //                                $text = strip_tags($arElement['PREVIEW_TEXT']);
                            //                                if (strlen($text) > 160) {
                            //                                    $text = substr($text, 0, 160) . '...';
                            //                                }
                            //                                $text = htmlentities($text);
                            //                                $ogData[$reqKey] = $text;
                            //                            }
                            break;
                        case 'og:url' :
                            if (!empty($arElement['DETAIL_PAGE_URL'])) {
                                $ogData[$reqKey] = $oModule->getUrl($arElement['DETAIL_PAGE_URL']);
                            }
                            break;
                        case 'og:image' :
                            if (!empty($arElement['DETAIL_PICTURE'])) {
                                $ogData[$reqKey] = $this->getImagePathById($arElement['DETAIL_PICTURE']);
                            } elseif (!empty($arElement['PREVIEW_PICTURE'])) {
                                $ogData[$reqKey] = $this->getImagePathById($arElement['PREVIEW_PICTURE']);
                            }
                            break;
                    }
                }
            }
        } elseif ($type === 'section') {
            $arSection = \CIBlockSection::GetByID($refId)->GetNext();
            if ($arSection) {
                foreach ($ogReqData as $reqKey) {
                    if (!$this->checkOnProperty('iblock_fields', $reqKey)) {
                        continue;
                    }
                    switch ($reqKey) {
                        case 'og:title' :
                            if (!empty($arSection['NAME'])) {
                                $ogData[$reqKey] = $arSection['NAME'];
                                //                                $ogData[$reqKey] = htmlentities($ogData[$reqKey]);
                            }
                            break;
                        case 'og:description' :
                            if (!empty($arSection['DESCRIPTION'])) {
                                $ogData[$reqKey] = $arSection['DESCRIPTION'];
                                //                                $text = strip_tags($arSection['DESCRIPTION']);
                                //                                if (strlen($text) > 160) {
                                //                                    $text = substr($text, 0, 160) . '...';
                                //                                }
                                //                                $text = htmlentities($text);
                                //                                $ogData[$reqKey] = $text;
                            }
                            break;
                        case 'og:url' :
                            if (!empty($arSection['SECTION_PAGE_URL'])) {
                                $ogData[$reqKey] = $oModule->getUrl($arSection['SECTION_PAGE_URL']);
                            }
                            break;
                        case 'og:image' :
                            if (!empty($arSection['DETAIL_PICTURE'])) {
                                $ogData[$reqKey] = $this->getImagePathById($arSection['DETAIL_PICTURE']);
                            } elseif (!empty($arSection['PICTURE'])) {
                                $ogData[$reqKey] = $this->getImagePathById($arSection['PICTURE']);
                            }
                            break;
                    }
                }
            }
        }
        return $ogData;
    }

    /**
     * Устанавливает ключи и значение из SetPageProperty и SetDirProperty
     * @param array $ogData - массив из OpenGraph ключ=>значение
     * @return array
     */
    public function setPropertyPropFields($ogData)
    {
        global $APPLICATION;
        $ogReqData = [];
        foreach ($this->ogFields as $reqKey) {
            if (empty($ogData[$reqKey])) {
                $ogReqData[] = $reqKey;
            }
        }
        if (!$ogReqData) return $ogData;
        $oModule = \dev2funModuleOpenGraphClass::getInstance();
        foreach ($ogReqData as $ogKey) {
            if (empty($ogData[$ogKey]) && $this->checkOnProperty('prop_fields', $ogKey)) {
                $ogValue = $APPLICATION->GetProperty($ogKey);
                if (!$ogValue) continue;
                switch ($ogKey) {
                    //                    case 'title':
                    //                        print_pre('PropFields');
                    //                        break;
                    case 'og:description' :
                        $ogValue = $APPLICATION->GetProperty('og:description');
                        if (!$ogValue) $ogValue = $APPLICATION->GetProperty('description');
                        break;
                    case 'og:image' :
                        if (!preg_match('#^(http|https)#', $ogValue)) {
                            $prefix = $oModule->getProtocol() . $oModule->getHost();
                            $ogValue = $prefix . $ogValue;
                        }
                        break;
                }
                $ogData[$ogKey] = $ogValue;
            }
        }
        return $ogData;
    }

    /**
     * Устанавливает ключи и значение по умолчанию
     * @param array $ogData - массив из OpenGraph ключ=>значение
     * @return array
     */
    public function setPropertyDefault($ogData)
    {
        global $APPLICATION;
        $ogReqData = [];
        foreach ($this->ogFields as $reqKey) {
            if (empty($ogData[$reqKey])) {
                $ogReqData[] = $reqKey;
            }
        }
        if (!$ogReqData) {
            return $ogData;
        }

        $pageGraph = new PageGraph();
        $pageDataGraph = $pageGraph->getPageData();
        $oModule = \dev2funModuleOpenGraphClass::getInstance();

        foreach ($ogReqData as $ogKey) {
            if (empty($ogData[$ogKey]) && $this->checkOnProperty('default', $ogKey)) {
                $ogValue = '';
                switch ($ogKey) {
                    case 'og:title':
                        $ogValue = $APPLICATION->GetProperty('title');
                        if (!$ogValue) {
                            $ogValue = $APPLICATION->GetTitle();
                        }
                        break;
                    case 'og:description':
                        $ogValue = $APPLICATION->GetProperty('description');
                        break;
                    case 'og:url':
                        $url = $oModule->getUrl($APPLICATION->GetCurPage());
                        $ogValue = $this->getPrepareUrl($url);
                        break;
                    case 'og:site_name':
                        $obSite = \CSite::GetByID(
                            Context::getCurrent()->getSite()
                        );
                        if ($arSite = $obSite->Fetch()) {
                            $ogValue = $arSite['SITE_NAME'];
                        }
                        break;
                    case 'og:image':
                        $img = $pageDataGraph->getPictureId();
                        if (!$img) {
                            $img = Option::get(\dev2funModuleOpenGraphClass::$module_id, 'DEFAULT_IMAGE');
                        }
                        if ($img) {
                            $ogValue = $this->getImagePathById($img);
                            if (!$ogValue) {
                                Option::set(\dev2funModuleOpenGraphClass::$module_id, 'DEFAULT_IMAGE', '');
                            }
                        }
                        break;
                    case 'og:image:type':
                        if (array_key_exists('og:image', $ogData)) {
                            if (!isset($imgsize)) {
                                $file = str_replace($oModule->getProtocol() . $oModule->getHost(), '', $ogData['og:image']);
                                $imgsize = getimagesize($_SERVER['DOCUMENT_ROOT'] . $file);
                            }
                            if (!empty($imgsize['mime'])) {
                                $ogValue = $imgsize['mime'];
                            }
                        }
                        break;
                    case 'og:image:width':
                        if (array_key_exists('og:image', $ogData)) {
                            if (!isset($imgsize)) {
                                $file = str_replace($oModule->getProtocol() . $oModule->getHost(), '', $ogData['og:image']);
                                $imgsize = getimagesize($_SERVER['DOCUMENT_ROOT'] . $file);
                            }
                            if (!empty($imgsize[0])) {
                                $ogValue = $imgsize[0];
                            }
                        }
                        break;
                    case 'og:image:height':
                        if (array_key_exists('og:image', $ogData)) {
                            if (!isset($imgsize)) {
                                $file = str_replace($oModule->getProtocol() . $oModule->getHost(), '', $ogData['og:image']);
                                $imgsize = getimagesize($_SERVER['DOCUMENT_ROOT'] . $file);
                            }
                            if (!empty($imgsize[1])) {
                                $ogValue = $imgsize[1];
                            }
                        }
                        break;
                    case 'og:image:secure_url':
                        if (!empty($ogData['image'])) {
                            $ogValue = $ogData['image'];
                        }
                        break;
                    case 'og:type':
                        $ogValue = 'website';
                        break;
                }
                if ($ogValue) {
                    $ogData[$ogKey] = $ogValue;
                }
            }
        }
        return $ogData;
    }

    /**
     * Get meta tags
     * @param array $ogData - OG_key=>OG_value
     * @return array
     */
    public function getMeta($ogData)
    {
        $arStr = [];
        if ($ogData) {
            foreach ($ogData as $ogKey => $ogValue) {
                if (!empty($ogValue)) {
                    $arStr[] = '<meta property="' . $ogKey . '" content="' . $ogValue . '"/>';
                }
            }
        }
        return $arStr;
    }

    /**
     * Add meta tags in header
     * @param array $arStr
     * @return void
     */
    public function addHeader($arStr)
    {
        if (!$arStr) {
            return;
        }
        $asset = \Bitrix\Main\Page\Asset::getInstance();
        $asset->addString('<!-- dev2fun module opengraph -->', true);
        foreach ($arStr as $str) {
            if (empty($str)) {
                continue;
            }
            $asset->addString($str, true);
        }
        $asset->addString('<!-- /dev2fun module opengraph -->', true);
    }

    /**
     * Add OpenGraph fields from $APPLICATION->GetProperty
     * And set default values
     * @param array $ogData - OG_key=>OG_value
     * @return array
     */
    public function setProperty($ogData)
    {
        $oModule = \dev2funModuleOpenGraphClass::getInstance();

        $this->ogFields = \dev2funModuleOpenGraphClass::getFields(true);
        $arSort = $oModule->getSettingsSortable();
        foreach ($arSort as $sort) {
            switch ($sort) {
                case 'prop_fields' :
                    $ogData = $this->setPropertyPropFields($ogData);
                    break;
                case 'default' :
                    $ogData = $this->setPropertyDefault($ogData);
                    break;
            }
        }
        return $ogData;
    }

    /**
     * Event Handler on delete element
     * @param array $arFields
     */
    public static function deleteElement($arFields)
    {
        if (!empty($arFields['ID'])) {
            $arRows = OpenGraphTable::getList(['filter' => [
                'REFERENCE_ID' => $arFields['ID'],
                'REFERENCE_TYPE' => 'element',
            ]]);
            if ($arRows) {
                foreach ($arRows as $arRow) {
                    OpenGraphTable::delete($arRow['ID']);
                }
            }
        }
    }

    /**
     * Event Handler on delete section
     * @param array $arFields
     */
    public static function deleteSection($arFields)
    {
        if (!empty($arFields['ID'])) {
            $arRows = OpenGraphTable::getList(['filter' => [
                'REFERENCE_ID' => $arFields['ID'],
                'REFERENCE_TYPE' => 'section',
            ]]);
            if ($arRows) {
                foreach ($arRows as $arRow) {
                    OpenGraphTable::delete($arRow['ID']);
                }
            }
        }
    }

    /**
     * Get url without index|index.php|index.html
     * @param string $url
     * @return string
     */
    public function getPrepareUrl($url)
    {
        if (!$url) return $url;
        if (empty($this->settings['REMOVE_INDEX'])) return $url;
        if ($this->settings['REMOVE_INDEX'] !== 'Y') return $url;
        return preg_replace('#(index|index\.php|index\.html)$#i', '', $url);
    }

    /**
     * Set all params
     * @param array $arParams
     */
    public function setParams($arParams)
    {
        $this->params = $arParams;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setParam($key, $value)
    {
        $this->params[$key] = $value;
    }

    /**
     * Get param
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getParam($key, $default = '')
    {
        return empty($this->params[$key]) ? $default : $this->params[$key];
    }

    /**
     * Get option from params
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOptionByParams($key, $default = '')
    {
        $options = $this->getOptionsByParams();
        return empty($options[$key]) ? $default : $options[$key];
    }

    /**
     * Get options from params
     * @return array
     */
    public function getOptionsByParams()
    {
        return $this->getParam('options', []);
    }

    public function getDefaultByField($fieldKey, $default = '')
    {
        $defaultFields = $this->getParam('default', []);
        return empty($defaultFields[$fieldKey]) ? $default : $defaultFields[$fieldKey];
    }

    /**
     * @param array $fields
     * @return array
     */
    public function prepareOpenGraphFields($fields)
    {
        if(in_array('og:image',$fields)) {
            if(\CMain::IsHTTPS()) {
                $fields[] = 'og:image:secure_url';
            }
            $fields[] = 'og:image:type';
            $fields[] = 'og:image:width';
            $fields[] = 'og:image:height';
        }

        return $fields;
    }

    /**
     * Возвращает обработанные поля
     * @param string $key
     * @param string $value
     * @return string
     */
    public function prepareFieldsValue($key, $value)
    {
        if (empty($value)) {
            return $value;
        }

        switch ($key) {
            case 'og:title':
            case 'og:site_name':
                $value = htmlentities($value);
                break;
            case 'og:description':
                if(!$value) break;
                $text = trim(strip_tags(html_entity_decode($value)));
                if (mb_strlen($text) > 160) {
                    $text = mb_substr($text, 0, 160) . '...';
                }
                $text = str_replace('  ','', $text);
                $value = htmlentities($text);
                break;
            case 'og:image':
                $oModule = \dev2funModuleOpenGraphClass::getInstance();
                if (!preg_match('#^(http|https)#', $value)) {
                    $prefix = $oModule->getProtocol() . $oModule->getHost();
                    $value = $prefix . $value;
                }
                break;
        }

        return $value;
    }

    /**
     * Возвращает обработанные поля
     * @param $fields
     * @return array
     */
    public function prepareFieldsValues($fields)
    {
        if(!$fields) return $fields;

        foreach ($fields as $key=>&$field) {
            $field = $this->prepareFieldsValue($key, $field);
        }
        unset($field);

        return $fields;
    }
}