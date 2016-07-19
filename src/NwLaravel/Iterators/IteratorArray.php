<?php

namespace NwLaravel\Iterators;

use ArrayIterator;

/**
 * Library Iterator Array
 */
class IteratorArray extends ArrayIterator implements IteratorInterface
{
    /**
     * @var array
     */
    protected $defaults = array();

    /**
     * @var array
     */
    protected $replace = array();

    /**
     * Get row current
     *
     * @return array
     */
    public function current()
    {
        $current = parent::current();
        $current = is_array($current) ? $current : array();
        return array_merge($this->defaults, $current, $this->replace);
    }

    /**
     * Set Fields Defaults
     *
     * @param array $defaults Defaults
     *
     * @return void
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * Set Fields Replace
     *
     * @param array $replace Replaces
     *
     * @return void
     */
    public function setReplace(array $replace)
    {
        $this->replace = $replace;
        return $this;
    }
}
