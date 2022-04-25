<?php
/**
 * @author dev2fun <darkfriend>
 * @copyright (c) 2022, darkfriend <hi@darkfriend.ru>
 * @version 1.4.0
 */

namespace Dev2fun\Module;

use Bitrix\Main\Context;

class PageDataGraph
{
    /**
     * @var array
     */
    private $values;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * @return null|string
     */
    public function getPicturePath()
    {
        if (!$this->values['picture']) {
            return null;
        }

        $server = Context::getCurrent()->getServer();
        $path = \CFile::GetPath($this->values['picture']);

        if (!file_exists($server->getDocumentRoot().$path)) {
            return null;
        }

        return $path;
    }

    /**
     * @return int|null
     */
    public function getPictureId()
    {
        if (!$this->values['picture']) {
            return null;
        }

        return (int)$this->values['picture'];
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->values['properties'] ?? [];
    }
}