<?php
/**
 * @author dev2fun <darkfriend>
 * @copyright (c) 2022, darkfriend <hi@darkfriend.ru>
 * @version 1.4.0
 */

namespace Dev2fun\Module;


class PagePathGraph
{
    /**
     * @var array
     */
    private $page;

    /**
     * @param array $pageData
     */
    public function __construct($pageData)
    {
        $this->page = $pageData;
    }

    /**
     * @return mixed|null
     */
    public function condition()
    {
        return $this->page['condition'] ?? null;
    }

    /**
     * @return mixed|null
     */
    public function variables()
    {
        return $this->page['variables'] ?? [];
    }

    /**
     * @return mixed|null
     */
    public function data()
    {
        return $this->page['data'] ?? [];
    }
}