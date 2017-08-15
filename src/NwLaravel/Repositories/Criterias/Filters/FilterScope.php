<?php
namespace NwLaravel\Repositories\Criterias\Filters;

class FilterScope implements FilterInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * Construct
     *
     * @param Model $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Filter
     *
     * @param Query\Builder $query
     * @param int|string    $key
     * @param mixed         $value
     *
     * @return boolean
     */
    public function filter($query, $key, $value)
    {
        $methodScope = 'scope' . studly_case($key);
        if (is_object($this->model) && method_exists($this->model, $methodScope)) {
            $methodName = camel_case($key);
            $query = $query->{$methodName}($value);
            return true;
        }

        return false;
    }
}
