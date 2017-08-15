<?php
namespace NwLaravel\Repositories\Criterias\Filters;

use Illuminate\Database\Query\Expression;

class FilterExpression implements FilterInterface
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
        if (is_int($key) && $value instanceof Expression) {
            $query = $query->whereRaw($value);
            return true;
        }

        return false;
    }
}
