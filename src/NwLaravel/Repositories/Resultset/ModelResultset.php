<?php

namespace NwLaravel\Repositories\Resultset;

use PDOStatement;
use RuntimeException;
use \Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Class ModelResultset
 */
class ModelResultset extends AbstractResultset
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model = null;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    private $prototype = null;

    /**
     * Initialize
     *
     * @param PDOStatement  $statement PDO Statement
     * @param EloquentModel $model     Model
     */
    public function __construct(PDOStatement $statement, EloquentModel $model)
    {
        $this->statement = $statement;
        $this->model = $model;

        $this->initialize();
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        if (! $this->initialized) {
            $this->prototype = $this->model->newFromBuilder();
        }

        $this->initialized = true;
    }

    /**
     * Get Prototype
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getPrototype()
    {
        return $this->prototype;
    }

    /**
     * Get the data
     *
     * @return object|bool
     */
    public function current()
    {
        if (!$data = parent::current()) {
            return false;
        }

        $newInstance = clone $this->getPrototype();
        $newInstance->setRawAttributes((array) $data, true);
        return $newInstance;
    }
}
