<?php
namespace NwLaravel\Iterators;

use Traversable;
use Countable;

/**
 * Interface do Result de Importação
 */
interface IteratorInterface extends Traversable, Countable
{
    /**
     * Set Fields Defaults
     *
     * @param array $defaults Defaults
     *
     * @return void
     */
    public function setDefaults(array $defaults);

    /**
     * Set Fields Replace
     *
     * @param array $replace Replaces
     *
     * @return void
     */
    public function setReplace(array $replace);
}
