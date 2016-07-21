<?php

namespace NwLaravel\Repositories\Resultset;

use PDO;
use Iterator;
use Countable;
use PDOStatement;
use RuntimeException;

/**
 * Class AbstractResultset
 */
abstract class AbstractResultset implements Iterator, Countable
{
    /**
     * @var \PDOStatement
     */
    protected $statement = null;

    /**
     * @var array Result options
     */
    protected $options;

    /**
     * Is the current complete?
     * @var bool
     */
    protected $rewindComplete = false;

    /**
     * Is initialized
     * @var bool
     */
    protected $initialized = false;

    /**
     * Track current item in recordset
     * @var mixed
     */
    protected $currentData = false;

    /**
     * Current position of scrollable statement
     * @var int
     */
    protected $position = -1;

    /**
     * @var int
     */
    protected $rowCount = null;

    /**
     * @var array
     */
    protected $fields = null;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->initialized = true;
    }

    /**
     * Get statement
     *
     * @throws RuntimeException
     * @return mixed
     */
    public function getStatement()
    {
        if (!$this->statement instanceof PDOStatement) {
            throw new RuntimeException("PDOStatement not defined, not initialize resultset");
        }

        return $this->statement;
    }

    /**
     * Get the data
     *
     * @return mixed
     */
    public function current()
    {
        return $this->currentData;
    }

    /**
     * Next
     *
     * @throws RuntimeException
     * @return mixed
     */
    public function next()
    {
        $this->position++;
        return $this->fetch();
    }

    /**
     * Fetch
     *
     * @throws RuntimeException
     * @return array
     */
    private function fetch()
    {
        return $this->currentData = $this->getStatement()->fetch(PDO::FETCH_ASSOC);
    }


    /**
     * Key
     *
     * @return mixed
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Reset interator
     *
     * @throws RuntimeException
     * @return void
     */
    public function rewind()
    {
        $this->initialize();

        if ($this->rewindComplete) {
            throw new RuntimeException("Rewind only once.");
        }

        if (!$this->currentData) {
            $this->fetch();
        }

        $this->rewindComplete = true;
        $this->position = 0;
    }

    /**
     * Valid
     *
     * @return bool
     */
    public function valid()
    {
        return ($this->currentData !== false);
    }

    /**
     * Count
     *
     * @throws RuntimeException
     * @return int
     */
    public function count()
    {
        if (is_int($this->rowCount)) {
            return $this->rowCount;
        }

        $this->rowCount = (int) $this->getStatement()->rowCount();

        return $this->rowCount;
    }

    /**
     * Fields
     *
     * @throws RuntimeException
     * @return array
     */
    public function getFields()
    {
        if (is_array($this->fields)) {
            return $this->fields;
        }

        $fields = array();

        if (!$this->currentData) {
            $this->fetch();
        }

        $current = $this->current();

        if (is_array($current)) {
            $fields = array_keys($current);

        } elseif (is_object($current) && method_exists($current, 'toArray')) {
            $fields = array_keys($current->toArray());
        }

        $this->fields = $fields;
        return $fields;
    }

    /**
     * Return numbers de fields
     *
     * @throws RuntimeException
     * @return int
     */
    public function getFieldCount()
    {
        return count($this->getFields());
    }
}
