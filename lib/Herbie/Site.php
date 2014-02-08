<?php

/*
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;


/**
 * Stores the site.
 */
class Site
{

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return string
     */
    public function getTime()
    {
        return date('c');
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->app['data'];
    }

    /**
     * @return MenuCollection
     */
    public function getMenu()
    {
        return $this->app['menu'];
    }

    /**
     * @return MenuTree
     */
    public function getTree()
    {
        return $this->app['tree'];
    }

    /**
     * @return PostCollection
     */
    public function getPosts()
    {
        return $this->app['posts'];
    }

    /**
     * @return string
     */
    public function getRootPath()
    {
        return $this->app['rootPath'];
    }

    /**
     * @return string
     */
    public function getLastCreated()
    {
        $lastCreated = 0;
        foreach($this->app['menu'] AS $item) {
            $modified = strtotime($item->getCreated());
            if($modified > $lastCreated) {
                $lastCreated = $modified;
            }
        }
        return date('c', $lastCreated);
    }

    /**
     * @return string
     */
    public function getLastModified()
    {
        $lastModified = 0;
        foreach($this->app['menu'] AS $item) {
            $modified = strtotime($item->getModified());
            if($modified > $lastModified) {
                $lastModified = $modified;
            }
        }
        return date('c', $lastModified);
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->app->language;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->app->locale;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->app->charset;
    }

}