<?php

namespace NwLaravel\Repositories\Criterias\Filters;

use Datetime;

class FilterDate implements FilterInterface
{
    /**
     * @var string
     */
    protected $operator;

    /**
     * @var mixed
     */
    private $grammar;

    /**
     * Construct
     *
     * @param string $operator
     */
    public function __construct($operator = '=')
    {
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
        if (is_string($value)) {
            $value = str_replace("_", '', $value); // Caso vier com a mascara __/__/____
        }

        if (!empty($value) || is_null($value)) {
            $date = asDateTime($value);

            if ($date instanceof Datetime && !is_null($value)) {
                $query = $query->whereRaw(
                    $this->dateFormatDb($query, $key, $this->operator),
                    [$date->format('Y-m-d')]
                );
            } else {
                if ($this->operator == '!=' || $this->operator == '<>') {
                    $query = $query->whereNotNull($key);
                } else {
                    $query = $query->whereNull($key);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Date Formate Database
     *
     * @param Builder  $query    Builder
     * @param string   $key      Column
     * @param string   $operator String Operator
     *
     * @return string
     */
    private function dateFormatDb($query, $key, $operator)
    {
        if (!$this->grammar) {
            $this->grammar = $query->getQuery()->getGrammar();
        }

        $key = $this->grammar->wrap($key);

        $formatDb = sprintf("%s %s ?", $key, $operator);

        switch (true) {
            case $this->grammar instanceof \Illuminate\Database\Query\Grammars\MySqlGrammar:
                $formatDb = sprintf("DATE(%s) %s ?", $key, $operator);
                break;

            case $this->grammar instanceof \Illuminate\Database\Query\Grammars\PostgresGrammar:
                $formatDb = sprintf("DATE_TRUNC('day', %s) %s ?", $key, $operator);
                break;

            case $this->grammar instanceof \Illuminate\Database\Query\Grammars\SQLiteGrammar:
                $formatDb = sprintf("strftime('%%Y-%%m-%%d', %s) %s ?", $key, $operator);
                break;

            case $this->grammar instanceof \Illuminate\Database\Query\Grammars\SqlServerGrammar:
                $formatDb = sprintf("CAST(%s AS DATE) %s ?", $key, $operator);
        }

        return $formatDb;
    }
}
