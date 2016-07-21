<?php

namespace NwLaravel\Repositories\Resultset;

use RuntimeException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Class BuilderResultset
 */
class BuilderResultset extends AbstractResultset
{
    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $builder = null;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    private $prototype = null;

    /**
     * Initialize
     *
     * @param Illuminate\Database\Eloquent\Builder $builder Builder
     */
    public function __construct(EloquentBuilder $builder)
    {
        $this->builder = $builder;

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
            $query = $this->builder->toBase();
            $sql = $query->toSql();
            $bindings = $query->getBindings();
            $conn = $query->getConnection();
            $this->statement = $conn->getPdo()->prepare($sql);
            $this->statement->execute($conn->prepareBindings($bindings));

            $this->prototype = $this->builder->getModel()->newFromBuilder();
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
     * @return object
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
