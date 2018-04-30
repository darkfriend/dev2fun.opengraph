<?php
/**
 * @author dev2fun <darkfriend>
 * @copyright (c) 2017, darkfriend <hi@darkfriend.ru>
 * @version 1.1.0
 */

namespace Dev2fun\Module;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Dev2fun\OpenGraph\OpenGraphTable;

class OpenGraph {

    public $lastError;
    private static $instance;
    private static $_type;
    public static $_init;
    public $ogFields;
    public $ogValues;
    public $ogOnProperty;
    /**
     * @var int reference id (element id or section id)
     */
    public $refId;
    /**
     * @var string reference type (element||section)
     */
    public $refType;

    /**
     * Singleton instance.
     * @return self
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function IsAddTab () {
        global $APPLICATION;
        $curPath = $APPLICATION->GetCurPage();
        switch ($curPath) {
            case (preg_match('#(iblock_element_edit)#',$curPath)==1) :
                $enableTabElement = Option::get(\dev2funModuleOpenGraphClass::$module_id,'ADDTAB_ELEMENT','N');
                self::$_type = 'element';
                return ($enableTabElement=='Y');
                break;
            case (preg_match('#(iblock_section_edit)#',$curPath)==1) :
                $enableTabSection = Option::get(\dev2funModuleOpenGraphClass::$module_id,'ADDTAB_SECTION','N');
                self::$_type = 'section';
                return ($enableTabSection=='Y');
                break;
        }
        return false;
    }

    /**
     * @param \CAdminTabControl $form
     */
    public function AddAdminTab(&$form){
        global $APPLICATION;
        Loader::includeModule("dev2fun.opengraph");
        if(!OpenGraph::$_init && self::IsAddTab()) {
            OpenGraph::$_init = true;
            $sTableID = 'opengraph_edition';
            Loader::includeModule("iblock");

            $module = \dev2funModuleOpenGraphClass::getInstance();
            $arFields = \dev2funModuleOpenGraphClass::getFields(true);
            $arOpenGraph = OpenGraph::getInstance()->getByRef($_REQUEST['ID'],self::$_type);

            ob_start();
            include_once __DIR__.'/../../lib/views/admin.php';
            $admLIST = ob_get_contents();
            ob_end_clean();

            $form->tabs[] = array(
                "DIV" => "dev2fun_edition_list",
                "TAB" => Loc::getMessage('DEV2FUN_OG_TAB_NAME'),
                "ICON"=> "main_user_edit",
                "TITLE"=> Loc::getMessage('DEV2FUN_OG_TAB_TITLE'),
                "CONTENT"=>'<tr><td colspan="2">'.$admLIST.'</td></tr>'
            );
        }
    }

    /**
     * Event Handler on save element
     * @param array &$arFields
     */
    public function saveElement(&$arFields) {
        if(!empty($arFields["ID"])) {
            $obParser = new \CTextParser;
            $ogFields = \dev2funModuleOpenGraphClass::getFields(true);
            $reqFields = $_REQUEST['DEV2FUN_OPENGRAPH'];
            if(
                isset($reqFields['title'])
                && empty($reqFields['title'])
                && !empty($arFields['NAME'])
            ) {
                $reqFields['title'] = $arFields['NAME'];
            }
            if(
                isset($reqFields['description'])
                && empty($reqFields['description'])
                && !empty($arFields['PREVIEW_TEXT'])
            ) {
                $reqFields['description'] = $obParser->html_cut($arFields['PREVIEW_TEXT'],121);
            } elseif(
                isset($reqFields['description'])
                && empty($reqFields['description'])
                && !empty($arFields['DETAIL_TEXT'])
            ) {
                $reqFields['description'] = $obParser->html_cut($arFields['DETAIL_TEXT'],121);
            }
            if(in_array('image',$ogFields)) {
                $file = [];
                if(!empty($_POST['OG_IMAGE'])) {
                    $file = $_POST['OG_IMAGE'];
                    if(!empty($_POST['OG_IMAGE_del']) && $_POST['OG_IMAGE_del']=='Y') {
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
                } elseif(!empty($arFields['PREVIEW_PICTURE_ID'])) {
                    $file = $arFields['PREVIEW_PICTURE_ID'];
                    if(is_numeric($file)) {
                        $file = \CFile::MakeFileArray($file);
                    }
                } elseif(!empty($_POST['PREVIEW_PICTURE'])) {
                    $file = $_POST['PREVIEW_PICTURE'];
                    if(is_numeric($file)) {
                        $file = \CFile::MakeFileArray($file);
                    }
                } elseif(!empty($arFields['DETAIL_PICTURE_ID'])) {
                    $file = $arFields['DETAIL_PICTURE_ID'];
                    if(is_numeric($file)) {
                        $file = \CFile::MakeFileArray($file);
                    }
                } elseif(!empty($_POST['DETAIL_PICTURE'])) {
                    $file = $_POST['DETAIL_PICTURE'];
                    if(is_numeric($file)) {
                        $file = \CFile::MakeFileArray($file);
                    }
                }

                if($file) {
                    if(is_numeric($file)) {
                        $reqFields['image'] = $file;
                    } else {
                        if(!file_exists($file['tmp_name'])){
                            $upload_dir = \COption::GetOptionString("main", "upload_dir", "upload");
                            $absPath = $_SERVER["DOCUMENT_ROOT"]."/".$upload_dir."/tmp";
                            if(!empty($file['tmp_name'])) {
                                $file['tmp_name'] = $absPath.$file['tmp_name'];
                            }
                        }
                        $fileID = \CFile::SaveFile($file,'dev2fun_opengraph', true);
                        if($fileID) $reqFields['image'] = $fileID;
                    }
                }
            }
            if($reqFields) {
                OpenGraph::getInstance()->save($arFields["ID"],'element', $reqFields);
            }
        }
    }

    public function deleteImage($id) {
        \CFile::Delete($id);
    }

    /**
     * Event Handler on save section
     * @param array &$arFields
     */
    public function saveSection(&$arFields) {
        if(!empty($arFields["ID"])) {
            $obParser = new \CTextParser;
            $ogFields = \dev2funModuleOpenGraphClass::getFields(true);
            $reqFields = $_REQUEST['DEV2FUN_OPENGRAPH'];
            if(
                isset($reqFields['title'])
                && empty($reqFields['title'])
                && !empty($arFields['NAME'])
            ) {
                $reqFields['title'] = $arFields['NAME'];
            }
            if(
                isset($reqFields['description'])
                && empty($reqFields['description'])
                && !empty($arFields['DESCRIPTION'])
            ) {
                $reqFields['description'] = $obParser->html_cut($arFields['DESCRIPTION'],121);
            }
            if(in_array('image',$ogFields)) {
                $file = [];
                if(!empty($_POST['OG_IMAGE'])) {
                    $file = $_POST['OG_IMAGE'];
                    if(!empty($_POST['OG_IMAGE_del']) && $_POST['OG_IMAGE_del']=='Y') {
                        OpenGraph::getInstance()->deleteImage($file);
                        $file = 0;
                        $reqFields['image'] = '';
                    }
                } elseif(!empty($_POST['PICTURE'])) {
                    $file = $_POST['PICTURE'];
                    if(is_numeric($file)) {
                        $file = \CFile::MakeFileArray($file);
                    }
                }
                if($file) {
                    if(is_numeric($file)) {
                        $reqFields['image'] = $file;
                    } else {
                        if(!file_exists($file['tmp_name'])){
                            $upload_dir = \COption::GetOptionString("main", "upload_dir", "upload");
                            $absPath = $_SERVER["DOCUMENT_ROOT"]."/".$upload_dir."/tmp";
                            if(!empty($file['tmp_name'])) {
                                $file['tmp_name'] = $absPath.$file['tmp_name'];
                            }
                        }
                        $fileID = \CFile::SaveFile($file,'dev2fun_opengraph', true);
                        if($fileID) $reqFields['image'] = $fileID;
                    }
                }
            }
            if($reqFields) {
                OpenGraph::getInstance()->save($arFields["ID"],'section', $reqFields);
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
    public function save($refId, $type, $fields) {
        if(!$refId||!$type||!$fields) return false;
        global $APPLICATION;
        $arRows = OpenGraphTable::getList(array('filter'=>array(
            'REFERENCE_ID' => $refId,
            'REFERENCE_TYPE' => $type,
        )));
        if($arRows) {
            foreach ($arRows as $arRow) {
                if($arRow['META_KEY']=='image' && empty($fields['image'])) {
                    $fields['image'] = $arRow['META_VAL'];
                }
                OpenGraphTable::delete($arRow['ID']);
            }
        }
        foreach ($fields as $metaKey=>$metaVal) {
            $metaKey = htmlspecialcharsbx($metaKey);
            $metaVal = htmlspecialcharsbx($metaVal);
            $rowFields = [
                'REFERENCE_ID' => $refId,
                'REFERENCE_TYPE' => $type,
                'META_KEY' => $metaKey,
                'META_VAL' => $metaVal,
            ];
            $res = OpenGraphTable::add($rowFields);
            if($res->isSuccess()){
                continue;
            } else {
                $arErrors = $res->getErrorMessages();
                $this->lastError = $res->getErrorMessages();
                $APPLICATION->ThrowException(implode(PHP_EOL,$arErrors));
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
    public function getByRef($refId,$type) {
        $res = $this->getList([
            'REFERENCE_ID' => $refId,
            'REFERENCE_TYPE' => $type,
        ]);
        if($res)
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
    public function getList($filter=[],$fields=[],$sort=[]) {
        $result = [];
        $res = $this->getQuery($filter,$fields,$sort);
        if($res) {
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
    public function getQuery($filter=[],$fields=[],$sort=[]) {
        $query = [];
        if($filter)
            $query['filter'] = $filter;
        if($fields)
            $query['select'] = $fields;
        if($sort)
            $query['order'] = $sort;
        return OpenGraphTable::getList($query);
    }

    /**
     * Output Open Graph meta tags
     * @param integer $refId - reference id (element id or section id)
     * @param string $type - reference type (element||section)
     * @param array $params - дополнительные параметры, например ogOnProperty.<br>
     * ogOnProperty - для того если допустим title установить только на определенном шаге. (og_fields,iblock_fields,prop_fields,default)
     * title => default - для того чтоб задать заголовок по умолчанию
     * @return bool
     */
    public static function Show($refId,$type='element',$params=[]) {
        global $APPLICATION;
        if(!$refId) return false;
        $og = OpenGraph::getInstance();
        $og->refId = $refId;
        $og->refType = $type;
        if(!empty($params['ogOnProperty']))
            $og->ogOnProperty = $params['ogOnProperty'];
        return true;
    }

    public static function SetEventHandler() {
        AddEventHandler("main", "OnEpilog", ['dev2funModuleOpenGraphClass','AddOpenGraph'], 999999999);
    }

    /**
     * Возвращает путь до картинки.
     * Ресайзит картинку, если нужно
     * @param int $imageId
     * @return string
     */
    public function getImagePath($imageId) {
        $settingsResize = \dev2funModuleOpenGraphClass::getInstance()->getSettingsResize();
        $imagePath = '';
        if($settingsResize['ENABLE']=='Y' && (!empty($settingsResize['WIDTH'])||!empty($settingsResize['HEIGHT']))) {
            if(empty($settingsResize['TYPE'])) $settingsResize['TYPE'] = BX_RESIZE_IMAGE_PROPORTIONAL;
            $arImage = \CFile::ResizeImageGet($imageId,[
                'width' => (!empty($settingsResize['WIDTH'])?$settingsResize['WIDTH']:99999),
                'height' => (!empty($settingsResize['HEIGHT'])?$settingsResize['HEIGHT']:99999),
            ],$settingsResize['TYPE']);
            if($arImage) {
                $imagePath = $arImage['src'];
            }
        }
        if(!$imagePath){
            $imagePath = \CFile::GetPath($imageId);
        }
        if($imagePath) {
            $oModule = \dev2funModuleOpenGraphClass::getInstance();
            $prefix = '';
			if(!preg_match('#^(http|https)#',$imagePath)){
				$prefix = $oModule->getProtocol().$oModule->getHost();
			}
            $imagePath = $prefix.$imagePath;
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
    public function setPropertyOpenGraphFields($ogData,$refId,$type) {
        $data = $this->getByRef($refId,$type);
        if($data) {
            foreach ($data as $ogKey=>$ogVal) {
                if(empty($ogData[$ogKey]) && $this->checkOnProperty('og_fields',$ogKey)) {
                    switch ($ogKey) {
                        case 'description' :
                            if($ogVal) {
                                $ogVal = trim(strip_tags(html_entity_decode($ogVal)));
                                if(strlen($ogVal)>160) {
                                    $ogVal = substr($ogVal,0,160).'...';
                                }
                            }
                            break;
                        case 'image' :
                            if($ogVal) {
                                $ogVal = $this->getImagePath($ogVal);
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
    public function checkOnProperty($keyStep, $ogKey) {
        if(empty($this->ogOnProperty)) return true;
        if(empty($this->ogOnProperty[$ogKey])) return true;
        if($this->ogOnProperty[$ogKey]==$keyStep) return true;
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
    public function setPropertyIBlockFields($ogData,$refId,$type) {
//        $ogReqData = $this->ogFields;
        $ogReqData = [];
        foreach ($this->ogFields as $k=>$reqKey) {
            if(empty($ogData[$reqKey])) {
                $ogReqData[]=$reqKey;
            }
        }
        if(!$ogReqData) return $ogData;
        $oModule = \dev2funModuleOpenGraphClass::getInstance();
        if($type=='element') {
            $arElement = \CIBlockElement::GetByID($refId)->GetNext();
            if($arElement) {
                foreach ($ogReqData as $reqKey) {
                    if(!$this->checkOnProperty('iblock_fields',$reqKey)){
                        continue;
                    }
                    switch ($reqKey) {
                        case 'title' :
                            if(!empty($arElement['NAME'])) {
                                $ogData[$reqKey] = $arElement['NAME'];
                            }
                            break;
                        case 'description' :
                            if(!empty($arElement['DETAIL_TEXT'])) {
                                $text = strip_tags($arElement['DETAIL_TEXT']);
                                if(strlen($text)>160) {
                                    $text = substr($text,0,160).'...';
                                }
                                $ogData[$reqKey] = $text;
                            } elseif (!empty($arElement['PREVIEW_TEXT'])) {
                                $text = strip_tags($arElement['PREVIEW_TEXT']);
                                if(strlen($text)>160) {
                                    $text = substr($text,0,160).'...';
                                }
                                $ogData[$reqKey] = $text;
                            }
                            break;
                        case 'url' :
                            if(!empty($arElement['DETAIL_PAGE_URL'])) {
                                $ogData[$reqKey] = $oModule->getUrl($arElement['DETAIL_PAGE_URL']);
                            }
                            break;
                        case 'image' :
                            if(!empty($arElement['DETAIL_PICTURE'])) {
                                $ogData[$reqKey] = $this->getImagePath($arElement['DETAIL_PICTURE']);
                            } elseif(!empty($arElement['PREVIEW_PICTURE'])) {
                                $ogData[$reqKey] = $this->getImagePath($arElement['PREVIEW_PICTURE']);
                            }
                            break;
                    }
                }
            }
        } elseif($type=='section') {
            $arSection = \CIBlockSection::GetByID($refId)->GetNext();
            if($arSection) {
                foreach ($ogReqData as $reqKey) {
                    if(!$this->checkOnProperty('iblock_fields',$reqKey)){
                        continue;
                    }
                    switch ($reqKey) {
                        case 'title' :
                            if(!empty($arSection['NAME'])) {
                                $ogData[$reqKey] = $arSection['NAME'];
                            }
                            break;
                        case 'description' :
                            if(!empty($arSection['DESCRIPTION'])) {
                                $text = strip_tags($arSection['DESCRIPTION']);
                                if(strlen($text)>160) {
                                    $text = substr($text,0,160).'...';
                                }
                                $ogData[$reqKey] = $text;
                            }
                            break;
                        case 'url' :
                            if(!empty($arSection['SECTION_PAGE_URL'])) {
                                $ogData[$reqKey] = $oModule->getUrl($arSection['SECTION_PAGE_URL']);
                            }
                            break;
                        case 'image' :
                            if(!empty($arSection['DETAIL_PICTURE'])) {
                                $ogData[$reqKey] = $this->getImagePath($arSection['DETAIL_PICTURE']);
                            } elseif(!empty($arSection['PICTURE'])) {
                                $ogData[$reqKey] = $this->getImagePath($arSection['PICTURE']);
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
    public function setPropertyPropFields($ogData) {
        global $APPLICATION;
        $ogReqData = [];
        foreach ($this->ogFields as $reqKey) {
            if(empty($ogData[$reqKey])) {
                $ogReqData[]=$reqKey;
            }
        }
        if(!$ogReqData) return $ogData;
        $oModule = \dev2funModuleOpenGraphClass::getInstance();
        foreach ($ogReqData as $ogKey) {
            if(empty($ogData[$ogKey])&&$this->checkOnProperty('prop_fields',$ogKey)) {
                $ogValue = $APPLICATION->GetProperty('og:'.$ogKey);
                if(!$ogValue) continue;
                switch ($ogKey) {
//                    case 'title':
//                        print_pre('PropFields');
//                        break;
                    case 'description' :
                        $ogValue = $APPLICATION->GetProperty('description');
                        break;
                    case 'image' :
                        if(!preg_match('#^(http|https)\:\\\\#',$ogValue)){
                            $prefix = $oModule->getProtocol().$oModule->getHost();
                            $ogValue = $prefix.$ogValue;
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
    public function setPropertyDefault($ogData) {
        global $APPLICATION;
        $ogReqData = [];
        foreach ($this->ogFields as $reqKey) {
            if(empty($ogData[$reqKey])) {
                $ogReqData[]=$reqKey;
            }
        }
        if(!$ogReqData) return $ogData;
        $oModule = \dev2funModuleOpenGraphClass::getInstance();
        foreach ($ogReqData as $ogKey)
        {
            if(empty($ogData[$ogKey])&&$this->checkOnProperty('default',$ogKey)) // || !empty($GLOBALS['AddOpenGraph'])
            {
                $ogValue = '';
                switch ($ogKey)
                {
                    case 'title' :
                        $ogValue = $APPLICATION->GetProperty('title');
                        break;
                    case 'description' :
                        $ogValue = $APPLICATION->GetProperty('description');
                        break;
                    case 'url' :
                        $ogValue = $oModule->getUrl($APPLICATION->GetCurPage());
                        break;
                    case 'site_name' :
                        $obSite = \CSite::GetByID(SITE_ID);
                        if ($arSite = $obSite->Fetch()) {
                            $ogValue = str_replace('"',"'",$arSite['SITE_NAME']);
                        }
                        break;
                    case 'image' :
                        $img = Option::get(\dev2funModuleOpenGraphClass::$module_id,'DEFAULT_IMAGE');
                        if($img) {
                            $ogValue = $this->getImagePath($img);
                            if(!$ogValue) {
                                Option::set(\dev2funModuleOpenGraphClass::$module_id,'DEFAULT_IMAGE','');
                            }
                        }
                        break;
                    case 'image:type' :
                        if(key_exists('image',$ogData)){
                            if(!isset($imgsize)){
                                $file = str_replace($oModule->getProtocol().$oModule->getHost(),'',$ogData['image']);
                                $imgsize = getimagesize($_SERVER['DOCUMENT_ROOT'].$file);
                            }
                            if(!empty($imgsize['mime'])) {
                                $ogValue = $imgsize['mime'];
                            }
                        }
                        break;
                    case 'image:width' :
                        if(key_exists('image',$ogData)){
                            if(!isset($imgsize)){
                                $file = str_replace($oModule->getProtocol().$oModule->getHost(),'',$ogData['image']);
                                $imgsize = getimagesize($_SERVER['DOCUMENT_ROOT'].$file);
                            }
                            if(!empty($imgsize[0])) {
                                $ogValue = $imgsize[0];
                            }
                        }
                        break;
                    case 'image:height' :
                        if(key_exists('image',$ogData)){
                            if(!isset($imgsize)){
                                $file = str_replace($oModule->getProtocol().$oModule->getHost(),'',$ogData['image']);
                                $imgsize = getimagesize($_SERVER['DOCUMENT_ROOT'].$file);
                            }
                            if(!empty($imgsize[1])) {
                                $ogValue = $imgsize[1];
                            }
                        }
                        break;
                    case 'type' :
                        $ogValue = 'website';
                        break;
                }
                if($ogValue) $ogData[$ogKey] = $ogValue;
            }
        }
        return $ogData;
    }

    /**
     * Get meta tags
     * @param array $ogData - OG_key=>OG_value
     * @return array
     */
    public function getMeta($ogData) {
        $arStr = [];
        if($ogData) {
            foreach ($ogData as $ogKey => $ogValue) {
                if (!empty($ogValue)) {
                    $arStr[] = '<meta property="og:' . $ogKey . '" content="' . $ogValue . '">';
                }
            }
        }
        return $arStr;
    }

    /**
     * Add meta tags in header
     * @param array $arStr
     */
    public function addHeader($arStr) {
        if(!$arStr) return;
        $asset = \Bitrix\Main\Page\Asset::getInstance();
        $asset->addString('<!-- dev2fun module opengraph -->', true);
        foreach ($arStr as $str) {
            if(empty($str)) continue;
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
    public function setProperty($ogData) {
        global $APPLICATION;
//        if(empty($ogData)) return [];
//        $arSettings = \dev2funModuleOpenGraphClass::getSettingFields();
//        $arReqData = \dev2funModuleOpenGraphClass::getFields();
        $oModule = \dev2funModuleOpenGraphClass::getInstance();

        $arReqData = \dev2funModuleOpenGraphClass::getFields(true);

        $this->ogFields = $arReqData;
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

        /*print_pre('setProperty',true);
        foreach ($ogData as $ogKey=>&$ogValue) {
            if(!$ogValue) {
                $ogValue = $APPLICATION->GetProperty('og:'.$ogKey);
            }
            if($ogValue) {
                switch ($ogKey) {
                    case 'image' :
                        $prefix = '';
                        if(!preg_match('#^(http|https)\:\\\\#',$ogValue)) {
                            $prefix = $oModule->getProtocol().$oModule->getHost();
                        }
                        $ogValue = $prefix.$ogValue;
                        break;
                }
                continue;
            }

            switch ($ogKey)
            {
                case 'title' :
                    $ogValue = $APPLICATION->GetTitle();
                    break;
                case 'description' :
                    $ogValue = $APPLICATION->GetProperty('description');
                    break;
                case 'url' :
                    $ogValue = $oModule->getUrl($APPLICATION->GetCurPage());
                    break;
                case 'site_name' :
                    $obSite = \CSite::GetByID(SITE_ID);
                    if ($arSite = $obSite->Fetch()) {
                        $ogValue = str_replace('"',"'",$arSite['SITE_NAME']);
                    }
                    break;
                case 'image' :
                    $img = Option::get(\dev2funModuleOpenGraphClass::$module_id,'DEFAULT_IMAGE');
                    if($img) {
                        $ogValue = $this->getImagePath($img);
                        if(!$ogValue) {
                            Option::set(\dev2funModuleOpenGraphClass::$module_id,'DEFAULT_IMAGE','');
                        }
//                        $img = \CFile::GetPath($img);
//                        if(!$img) {
//                            Option::set(\dev2funModuleOpenGraphClass::$module_id,'DEFAULT_IMAGE','');
//                        }
//                        if(!preg_match('#^(http|https)\:\\\\#',$img)){
//                            $prefix = $oModule->getProtocol().$oModule->getHost();
//                            $img = $prefix.$img;
//                        }
//                        $ogValue = $img;
                    }
                    break;
                case 'image:type' :
                    if(key_exists('image',$ogData)){
                        if(!isset($imgsize)){
                            $file = str_replace($oModule->getProtocol().$oModule->getHost(),'',$ogData['image']);
                            $imgsize = getimagesize($_SERVER['DOCUMENT_ROOT'].$file);
                        }
                        if(!empty($imgsize['mime'])) {
                            $ogValue = $imgsize['mime'];
                        }
                    }
                    break;
                case 'image:width' :
                    if(key_exists('image',$ogData)){
                        if(!isset($imgsize)){
                            $file = str_replace($oModule->getProtocol().$oModule->getHost(),'',$ogData['image']);
                            $imgsize = getimagesize($_SERVER['DOCUMENT_ROOT'].$file);
                        }
                        if(!empty($imgsize[0])) {
                            $ogValue = $imgsize[0];
                        }
                    }
                    break;
                case 'image:height' :
                    if(key_exists('image',$ogData)){
                        if(!isset($imgsize)){
                            $file = str_replace($oModule->getProtocol().$oModule->getHost(),'',$ogData['image']);
                            $imgsize = getimagesize($_SERVER['DOCUMENT_ROOT'].$file);
                        }
                        if(!empty($imgsize[1])) {
                            $ogValue = $imgsize[1];
                        }
                    }
                    break;
                case 'type' :
                    $ogValue = 'website';
                    break;
            }
        }*/
        return $ogData;
    }

    /**
     * Event Handler on delete element
     * @param array $arFields
     */
    public function deleteElement($arFields) {
        if(!empty($arFields['ID'])){
            $arRows = OpenGraphTable::getList(['filter'=>[
                'REFERENCE_ID' => $arFields['ID'],
                'REFERENCE_TYPE' => 'element',
            ]]);
            if($arRows) {
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
    public function deleteSection($arFields) {
        if(!empty($arFields['ID'])){
            $arRows = OpenGraphTable::getList(['filter'=>[
                'REFERENCE_ID' => $arFields['ID'],
                'REFERENCE_TYPE' => 'section',
            ]]);
            if($arRows) {
                foreach ($arRows as $arRow) {
                    OpenGraphTable::delete($arRow['ID']);
                }
            }
        }
    }
}