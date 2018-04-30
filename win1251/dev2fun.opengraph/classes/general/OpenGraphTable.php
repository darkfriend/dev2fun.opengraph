<?php
/**
 * Table for DB
 * @author darkfriend <hi@darkfriend.ru>
 * @version 1.1.0
 */
namespace Dev2fun\OpenGraph;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

//Loc::loadMessages(__FILE__);
IncludeModuleLangFile(__FILE__);

class OpenGraphTable extends Entity\DataManager {

    static $module_id = "dev2fun.opengraph";

    public static function getFilePath() {
        return __FILE__;
    }

    public static function getTableName() {
        return 'b_d2f_opengraph_meta';
    }

    public static function getTableTitle() {
        return Loc::getMessage('DEV2FUN_OPENGRAPH_TABLE_TITLE');
    }

    public static function getMap() {
        return array(
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true,
            )),
            new Entity\IntegerField('REFERENCE_ID'),
            new Entity\StringField('META_KEY'),
            new Entity\StringField('META_VAL'),
            new Entity\StringField('REFERENCE_TYPE'), // element|section
//            new Entity\StringField('META_TYPE'), // file
        );
    }
}