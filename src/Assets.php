<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

/**
 * @see: http://fuelphp.com/docs/classes/asset/usage.html
 * @see: http://docs.phalconphp.com/en/latest/reference/assets.html
 */
class Assets
{
    const TYPE_CSS = 0;
    const TYPE_JS = 1;

    /**
     * @var \Herbie\Application
     */
    protected $app;

    /**
     * @var array
     */
    protected $assets = [];

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $assetsDir = 'assets';

    /**
     * @var string
     */
    protected $assetsUrl;

    /**
     * @var string
     */
    protected $assetsPath;

    /**
     * @var int
     */
    protected $refresh = 86400;

    /**
     * @var octal
     */
    protected $chmode = 0755;

    /**
     * @var int
     */
    protected static $counter = 0;

    /**
     * @var bool
     */
    protected static $sorted = false;

    /**
     * @var bool
     */
    protected static $published = false;

    /**
     * @param Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->baseUrl = $app['request']->getBaseUrl();
        $this->assetsPath = $app['webPath'] . '/' . $this->assetsDir;
        $this->assetsUrl = $this->baseUrl . '/' . $this->assetsDir;
    }

    /**
     * @param array|string $paths
     * @param array $attr
     * @param string $group
     * @param bool $raw
     * @param int $pos
     */
    public function addCss($paths, $attr = [], $group = null, $raw = false, $pos = 1)
    {
        $paths = is_array($paths) ? $paths : [$paths];
        foreach ($paths as $path) {
            $this->addAsset(self::TYPE_CSS, $path, $group, $attr, $raw, $pos);
        }
    }

    /**
     * @param array|string $paths
     * @param array $attr
     * @param string $group
     * @param bool $raw
     * @param int $pos
     */
    public function addJs($paths, $attr = [], $group = null, $raw = false, $pos = 1)
    {
        $paths = is_array($paths) ? $paths : [$paths];
        foreach ($paths as $path) {
            $this->addAsset(self::TYPE_JS, $path, $attr, $group, $raw, $pos);
        }
    }

    /**
     * @param string $group
     */
    public function outputCss($group = null)
    {
        $this->sort();
        $this->publish();
        foreach ($this->find(self::TYPE_CSS, $group) as $asset) {
            $href = $this->buildUrl($asset['path']);
            echo sprintf('<link href="%s" type="text/css" rel="stylesheet">', $href);
        }
    }

    /**
     * @param string $group
     */
    public function outputJs($group = null)
    {
        $this->sort();
        $this->publish();
        foreach ($this->find(self::TYPE_JS, $group) as $asset) {
            $href = $this->buildUrl($asset['path']);
            echo sprintf('<script src="%s"></script>', $href);
        }
    }

    /**
     * @param int $type
     * @param string $path
     * @param array $attr
     * @param string $group
     * @param bool $raw
     * @param int $pos
     */
    protected function addAsset($type, $path, $attr, $group, $raw, $pos)
    {
        $this->assets[] = [
            'type' => $type,
            'path' => $path,
            'group' => $group,
            'attr' => $attr,
            'raw' => $raw,
            'pos' => $pos,
            'counter' => ++self::$counter
        ];
    }

    /**
     * return void
     */
    protected function sort()
    {
        if (!self::$sorted) {
            uasort($this->assets, function($a, $b) {
                if ($a['pos'] == $b['pos']) {
                    if ($a['counter'] < $b['counter']) {
                        return -1;
                    }
                }
                if ($a['pos'] < $b['pos']) {
                    return -1;
                }
                return 1;
            });
            self::$sorted = true;
        }
    }

    /**
     * @param int $type
     * @param string $group
     * @return array
     */
    protected function find($type, $group = null)
    {
        $assets = [];
        foreach ($this->assets as $asset) {
            if (($asset['type'] == $type) && ($asset['group'] == $group)) {
                $assets[] = $asset;
            }
        }
        return $assets;
    }

    /**
     * @return void
     */
    protected function publish()
    {
        if (self::$published) {
            return;
        }
        foreach ($this->assets as $asset) {

            extract($asset); // type, path, group, attr, raw, pos, counter

            if (0 === strpos($path, '//') || 0 === strpos($path, 'http')) {
                continue;
            }

            $alias = $path;
            $source = $this->app->getAlias($alias);
            $dest = $this->assetsPath . '/' . $this->removeAlias($alias);
            $destDir = dirname($dest);
            if (!is_dir($destDir)) {
                mkdir($destDir, $this->chmode, true);
            }
            $copy = false;
            if (is_file($dest)) {
                $delta = time() - filemtime($dest);
                if ($delta > $this->refresh) {
                    $copy = true;
                }
            } else {
                $copy = true;
            }
            if ($copy) {
                copy($source, $dest);
            }
        }
        self::$published = true;
    }

    /**
     * @param string $file
     * @return string
     */
    protected function buildUrl($file)
    {
        $url = $file;
        if ('@' == substr($file, 0, 1)) {
            $trimed = $this->removeAlias($file);
            $url = $this->assetsUrl . '/' . $trimed;
        }
        return $url;
    }

    /**
     * @param string $file
     * @return string
     */
    protected function removeAlias($file)
    {
        $parts = explode('/', $file);
        array_shift($parts);
        return implode('/', $parts);
    }
}
