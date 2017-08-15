<?php

namespace NwLaravel\Repositories\Criterias\Filters;

class FilterArray implements FilterInterface
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
        if (is_int($key)) {
            /**
             * Using String Format
             * eg: {field},{operator},{value}
             */
            if (is_string($value) && preg_match('/^([a-zA-Z0-9_]+),(.+),(.+)$/', $value, $matches)) {
                $value = array_splice($matches, 1, 3);
            }

            /**
             * Using Array com Operator
             * eg: ex: ('field', '=', 'value') or ('field', 'value')
             */
            if (is_array($value) && count($value)) {
                $value = array_pad($value, 3, null);
                list($field, $operator, $valor) = array_splice($value, 0, 3);
                $filterWhere = new FilterWhere($this->table, $this->columns, $this->dates, $operator);
                return $filterWhere->filter($query, $field, $valor);
            }
        }

        return false;
    }
}
