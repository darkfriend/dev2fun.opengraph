<?php
/**
 * @author dev2fun <darkfriend>
 * @copyright (c) 2022, darkfriend <hi@darkfriend.ru>
 * @version 1.4.0
 */

namespace Dev2fun\Module;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;

class PageGraph
{
    private $siteId;
    private $siteDir;

    public function __construct()
    {
        $this->siteId = Context::getCurrent()->getSite();
        $this->siteDir = \SITE_DIR;
    }

    /**
     * @return string
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @return string
     */
    public function getSiteDir()
    {
        return $this->siteDir;
    }

    /**
     * @return PageDataGraph
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getPageData()
    {
        $pageGraph = $this->getPagePath();
        $data = [];

        if($pageGraph->data()) {
            $variables = $pageGraph->variables();
            $type = '';
            $extType = $pageGraph->data()['key'];

            switch ($extType) {
                case 'Element':
                case 'CatalogElement':
                    $data = ElementTable::getList([
                            'filter' => $variables['ELEMENT_CODE']
                                ? ['CODE' => $variables['ELEMENT_CODE']]
                                : ['ID' => $variables['ELEMENT_ID']],
                            'select' => [
                                'ID',
                                'PREVIEW_PICTURE',
                                'DETAIL_PICTURE',
                            ],
                            'cache' => [
                                'ttl' => 86400,
                                'cache_joins' => true,
                            ],
                        ])
                        ->fetch();
//                    $ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($variables["IBLOCK_ID"], $variables['ELEMENT_ID']);
//                    $data['properties'] = $ipropValues->getValues();
                    $type = 'element';
                    $data['picture'] = $data['PREVIEW_PICTURE'] ?: $data['DETAIL_PICTURE'];
                    break;


                case 'Section':
                case 'CatalogSection':
                    $data = SectionTable::getList([
                            'filter' => $variables['SECTION_CODE']
                                ? ['CODE' => $variables['SECTION_CODE']]
                                : ['ID' => $variables['SECTION_ID']],
                            'select' => ['ID', 'PICTURE'],
                            'cache' => [
                                'ttl' => 86400,
                                'cache_joins' => true,
                            ],
                        ])
                        ->fetch();
//                    $ipropValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($variables["IBLOCK_ID"], $variables['ELEMENT_ID']);
//                    $data['properties'] = $ipropValues->getValues();
                    $type = 'section';
                    $data['picture'] = $data['PICTURE'];
                    break;
            }

            $data['type'] = $type;
            $data['extType'] = $extType;
        }

        return new PageDataGraph((array)$data);
    }

    /**
     * @return PagePathGraph
     * @throws \Bitrix\Main\LoaderException
     */
    public function getPagePath(): PagePathGraph
    {
        $sefFolder = $this->siteDir;
        $urls = $this->getIblockUrls();
        $sefUrlTemplates = [];
        $variables = [];

        foreach ($urls as $urlTemplate => $item) {
            if (strpos($urlTemplate, $sefFolder) === 0) {
                $urlTemplate = substr($urlTemplate, strlen($sefFolder));
            }

            $url = str_replace('#SITE_DIR#/', '', $urlTemplate);
            $url = str_replace('#SITE_DIR#', '', $url);
            $url = str_replace('//', '/', $url);

            $sefUrlTemplates[($uniquePageId = 'p' . md5($item['key'] . $url))] = $url;
            $urls[$uniquePageId] = [
                'urlTemplate' => $urlTemplate,
                'data' => $item,
            ];
        }

        $componentEngine = new \CComponentEngine;

        if (Loader::includeModule('iblock')) {
            $componentEngine->addGreedyPart('#SECTION_CODE_PATH#');
            $componentEngine->addGreedyPart('#SMART_FILTER_PATH#');
            $componentEngine->setResolveCallback(['CIBlockFindTools', 'resolveComponentEngine']);
        }

        $arUrlTemplates = \CComponentEngine::makeComponentUrlTemplates([], $sefUrlTemplates);
        $uniqPageId = $componentEngine->guessComponentPath($sefFolder, $arUrlTemplates, $variables);

        if ($urls[$uniqPageId]['data']['isIblock']) {
            $component = new \CBitrixComponent;

            $component->arParams['IBLOCK_ID'] = $urls[$uniqPageId]['data']['id'];
            $component->arParams['DETAIL_STRICT_SECTION_CHECK'] = 'N';

            $componentEngine = new \CComponentEngine($component);

            $componentEngine->addGreedyPart("#SECTION_CODE_PATH#");
            $componentEngine->addGreedyPart("#SMART_FILTER_PATH#");
            $componentEngine->setResolveCallback(['CIBlockFindTools', 'resolveComponentEngine']);

            $uniqPageId = $componentEngine->guessComponentPath($sefFolder, $arUrlTemplates, $variables);

            $variables['IBLOCK_ID'] = $component->arParams['IBLOCK_ID'];

            if (strpos($urls[$uniqPageId]['data']['key'], 'Element') !== false) {
                if (!$variables['ELEMENT_CODE'] && $variables['CODE']) {
                    $variables['ELEMENT_CODE'] = $variables['CODE'];
                }

                if (!$variables['ELEMENT_ID'] && $variables['ID']) {
                    $variables['ELEMENT_ID'] = $variables['ID'];
                }
            }

            if (strpos($urls[$uniqPageId]['data']['key'], 'Section') !== false) {
                if (!$variables['SECTION_CODE'] && $variables['CODE']) {
                    $variables['SECTION_CODE'] = $variables['CODE'];
                }

                if (!$variables['SECTION_ID'] && $variables['ID']) {
                    $variables['SECTION_ID'] = $variables['ID'];
                }
            }
        }

        if ($urls[$uniqPageId]) {
            if (strpos($urls[$uniqPageId]['url_template'], '?') !== false) {
                $urlTemplateItem = parse_url($urls[$uniqPageId]['url_template']);

                if ($urlTemplateItem['query']) {
                    $urlTemplateQuery = [];
                    parse_str($urlTemplateItem['query'], $urlTemplateQuery);

                    foreach ($urlTemplateQuery as $n => $v) {
                        if (array_key_exists($n, $_REQUEST) && strlen($_REQUEST[$n]) > 0) {
                            $variables[$n] = $_REQUEST[$n];
                        }
                    }
                }
            }

            return new PagePathGraph([
                'data' => $urls[$uniqPageId]['data'],
                'condition' => str_replace('#SITE_DIR#/', '#SITE_DIR#', $urls[$uniqPageId]['url_template']),
                'variables' => $variables,
            ]);
        }

        $server = Context::getCurrent()->getServer();

        return new PagePathGraph([
            'data' => [],
            'condition' => '#SITE_DIR#' . substr($server->getRequestUri(), strlen($this->siteDir)),
            'variables' => [],
        ]);
    }

    /**
     * @return array
     * @throws \Bitrix\Main\LoaderException
     */
    public function getIblockUrls()
    {
        $urls = [];

        $obCache = new \CPHPCache();
        $cacheTime = 3600 * 24 * 365;
//        $cacheTime = 1;
        $cacheId = md5(__METHOD__ . $this->siteId);
        $cachePath = \dev2funModuleOpenGraphClass::$module_id . '/iblock_urls/';

        if ($obCache->InitCache($cacheTime, $cacheId, $cachePath)) {
            $urls = $obCache->GetVars();
        } elseif ($obCache->StartDataCache()) {
            $catalogIblocks = [];
            $catalogSkuIblocks = [];

            if (Loader::includeModule('catalog')) {
                $db = \CCatalog::getList();

                while (($a = $db->fetch()) !== false) {
                    if (is_array(\CCatalogSKU::GetInfoByOfferIBlock($a['IBLOCK_ID']))) {
                        $catalogSkuIblocks[] = $a['IBLOCK_ID'];
                    } else {
                        $catalogIblocks[] = $a['IBLOCK_ID'];
                    }
                }
            }

            $db = \CIBlock::GetList(
                [],
                [
                    'SITE_ID' => $this->siteId,
                    'ACTIVE' => 'Y',
                    'CHECK_PERMISSIONS' => 'Y',
                    '!ID' => $catalogSkuIblocks,
                ],
                false
            );
            while (($a = $db->fetch()) !== false) {
                if (!empty($urls[$a['DETAIL_PAGE_URL']])) {
                    continue;
                }

                $prefix = in_array($a['ID'], $catalogIblocks) ? 'Catalog' : '';

                if (strpos($a['SECTION_PAGE_URL'], '#IBLOCK_CODE#')) {
                    $a['SECTION_PAGE_URL'] = str_replace('#IBLOCK_CODE#', $a['CODE'], $a['SECTION_PAGE_URL']);
                }

                if (strpos($a['DETAIL_PAGE_URL'], '#IBLOCK_CODE#')) {
                    $a['DETAIL_PAGE_URL'] = str_replace('#IBLOCK_CODE#', $a['CODE'], $a['DETAIL_PAGE_URL']);
                }

                if ($a['SECTION_PAGE_URL']) {
                    $urls[$a['SECTION_PAGE_URL']] = [
                        'id' => $a['ID'],
                        'key' => $prefix . 'Section',
                        'isIblock' => true,
                    ];
                }

                $urls[$a['DETAIL_PAGE_URL']] = [
                    'id' => $a['ID'],
                    'key' => $prefix . 'Element',
                    'isIblock' => true,
                ];
            }

            $obCache->EndDataCache($urls);
        }

        return $urls;
    }
}