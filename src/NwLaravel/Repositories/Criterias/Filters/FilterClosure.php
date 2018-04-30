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
        if ($value instanceof \Closure) {
            if (is_int($key)) {
                $query = $query->where($value);
            } else {
                $query = $query->whereIn($key, $value);
            }
            return true;
        }

        return false;
    }
}
