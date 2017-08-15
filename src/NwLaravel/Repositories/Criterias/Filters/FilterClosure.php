<?php
namespace NwLaravel\Repositories\Criterias\Filters;

class FilterClosure implements FilterInterface
{
    /**
     * Filter
     *
     * @param Query\Builder $query
     * @param int|string $key
     * @param mixed      $value
     *
     * @return boolean
     */
    public function filter($query, $key, $value)
    {
        if (is_int($key) && $value instanceof \Closure) {
            $query = $query->where($value);
            return true;
        }

        return false;
    }
}
