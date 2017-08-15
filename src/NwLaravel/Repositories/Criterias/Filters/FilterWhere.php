<?php

namespace NwLaravel\Repositories\Criterias\Filters;

use InvalidArgumentException;

class FilterWhere implements FilterInterface
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
     * @var string
     */
    protected $operator;

    /**
     * Construct
     *
     * @param string $table
     * @param array  $columns
     * @param array  $dates
     * @param string $operator
     */
    public function __construct($table, $columns, $dates, $operator = '=')
    {
        $this->table = $table;
        $this->columns = $columns;
        $this->dates = $dates;
        $this->operator = $operator;
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
        $operator = $this->operator;

        $validOperator = function ($operator) {
            $operators = ['=', '<', '>', '<=', '>=', '<>', '!='];
            return in_array($operator, $operators);
        };

        if (! $validOperator($operator)) {
            throw new InvalidArgumentException("Illegal operator and value combination.");
        }

        // Raw Expression with Bidding
        if (strpos($key, '?') !== false) {
            $query = $query->whereRaw($key, (array) $value);
            return true;
        }

        $table = $this->table;
        $column = $key;
        if (preg_match('/^([a-zA-Z0-9_\-]+)\.([a-zA-Z0-9_\-]+)/', $key, $matches)) {
            $table = $matches[1];
            $column = $matches[2];
        }

        // Montagem Tabela com Coluns
        $key = $table.'.'.$column;

        // Attributes Valids
        if (in_array($column, $this->columns)) {
            if (is_null($value)) {
                if ($operator == '!=' || $operator == '<>') {
                    $query = $query->whereNotNull($key);
                } else {
                    $query = $query->whereNull($key);
                }
                return true;
            }

            if (in_array($column, $this->dates)) {
                $filterDate = new FilterDate($operator);
                return $filterDate->filter($query, $key, $value);
            }

            // Using Where In With An Array
            if (is_array($value)) {
                if ($operator == '!=' || $operator == '<>') {
                    $query = $query->whereNotIn($key, $value);
                } else {
                    $query = $query->whereIn($key, $value);
                }
                return true;
            }

            // Busca Direta
            $query = $query->where($key, $operator, $value);

            return true;
        }

        $filterBetween = new FilterBetween($this->table, $this->columns, $this->dates);
        return $filterBetween->filter($query, $key, $value);
    }
}
