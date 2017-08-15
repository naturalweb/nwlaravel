<?php

namespace NwLaravel\Repositories\Criterias\Filters;

class FilterBetween implements FilterInterface
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var array
     */
    protected $columns;

    /**
     * @var array
     */
    protected $dates;

    /**
     * Construct
     *
     * @param string $table
     * @param array  $columns
     * @param array  $dates
     */
    public function __construct($table, $columns, $dates)
    {
        $this->table = $table;
        $this->columns = $columns;
        $this->dates = $dates;
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
        $table = $this->table;
        $column = $key;
        if (preg_match('/^([a-zA-Z0-9_\-]+)\.([a-zA-Z0-9_\-]+)/', $key, $matches)) {
            $table = $matches[1];
            $column = $matches[2];
        }

        if (preg_match('/^(.+)(_ini|_fim)$/', $column, $match) && in_array($match[1], $this->columns)) {
            $field = $match[1];
            $operator = ($match[2]=='_ini')? '>=' : '<=';
            if (in_array($field, $this->dates)) {
                $filterDate = new FilterDate($operator);
                return $filterDate->filter($query, $table.'.'.$field, $value);
            }
            
            $query = $query->where($table.'.'.$field, $operator, $value);
            return true;
        }

        return false;
    }
}
