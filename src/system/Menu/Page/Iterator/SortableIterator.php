<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu\Page\Iterator;

use Herbie\Iterator\SplFileInfo as HerbieSplFileInfo;

class SortableIterator implements \IteratorAggregate
{

    const SORT_BY_NAME = 1;
    const SORT_BY_TYPE = 2;
    const SORT_BY_ACCESSED_TIME = 3;
    const SORT_BY_CHANGED_TIME = 4;
    const SORT_BY_MODIFIED_TIME = 5;

    /**
     * @var \Traversable
     */
    private $iterator;

    /**
     * @var int|callable
     */
    private $sort;

    /**
     * @param \Traversable $iterator
     * @param int|callable $sort
     * @throws \InvalidArgumentException
     */
    public function __construct(\Traversable $iterator, $sort)
    {
        $this->iterator = $iterator;

        if (self::SORT_BY_NAME === $sort) {
            $this->sort = function (HerbieSplFileInfo $a, HerbieSplFileInfo $b) {
                return strcmp($a->getRealPath(), $b->getRealPath());
            };
        } elseif (self::SORT_BY_TYPE === $sort) {
            $this->sort = function (HerbieSplFileInfo $a, HerbieSplFileInfo $b) {
                if ($a->isDir() && $b->isFile()) {
                    return -1;
                } elseif ($a->isFile() && $b->isDir()) {
                    return 1;
                }
                return strcmp($a->getRealPath(), $b->getRealPath());
            };
        } elseif (self::SORT_BY_ACCESSED_TIME === $sort) {
            $this->sort = function (HerbieSplFileInfo $a, HerbieSplFileInfo $b) {
                return ($a->getATime() - $b->getATime());
            };
        } elseif (self::SORT_BY_CHANGED_TIME === $sort) {
            $this->sort = function (HerbieSplFileInfo $a, HerbieSplFileInfo $b) {
                return ($a->getCTime() - $b->getCTime());
            };
        } elseif (self::SORT_BY_MODIFIED_TIME === $sort) {
            $this->sort = function (HerbieSplFileInfo $a, HerbieSplFileInfo $b) {
                return ($a->getMTime() - $b->getMTime());
            };
        } elseif (is_callable($sort)) {
            $this->sort = $sort;
        } else {
            $message = 'The SortableIterator takes a PHP callable or a valid built-in sort algorithm as an argument.';
            throw new \InvalidArgumentException($message);
        }
    }

    public function getIterator()
    {
        $array = iterator_to_array($this->iterator, true);
        uasort($array, $this->sort);

        return new \ArrayIterator($array);
    }
}
