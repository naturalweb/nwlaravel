<?php
namespace NwLaravel\Repositories\Criterias\Filters;

interface FilterInterface
{
    /**
     * Filter
     *
     * @param Query\Builder $query
     * @param int|string    $key
     * @param mixed         $value
     *
     * @return boolean
     */
    public function filter($query, $key, $value);
}
