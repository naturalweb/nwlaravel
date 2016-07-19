<?php

namespace NwLaravel\Repositories\Criterias;

use Datetime;
use InvalidArgumentException;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Illuminate\Database\Query\Expression as QueryExpression;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use NwLaravel\Entities\AbstractEntity;

/**
 * Class InputCriteria
 */
class InputCriteria implements CriteriaInterface
{
    /**
     * @var array
     */
    protected $input = [];

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @var array
     */
    protected $dates = [];

    /**
     * @var string
     */
    protected $table = '';

    /**
     * @var string
     */
    protected $nameSearchable = 'search';

    /**
     * @var array
     */
    protected $searchables = [];

    /**
     * @var string
     */
    protected $orderBy = null;

    /**
     * @var string
     */
    protected $sortedBy = 'asc';

    /**
     * @var RepositoryInterface
     */
    protected $repository = null;

    /**
     * @var Grammar
     */
    private $grammar = null;

    /**
     * Construct
     *
     * @param array $input
     */
    public function __construct(array $input = array())
    {
        $this->input = array_filter($input, function ($value) {
            return (!empty($value) || $value == "0" || is_null($value));
        });
    }

    /**
     * Add Columns
     *
     * @param array $columns Array Columns
     *
     * @return this
     */
    public function addColumns(array $columns)
    {
        $this->columns = array_unique(array_merge($this->columns, $columns));
        return $this;
    }

    /**
     * Set Fields Searchable
     *
     * @param array $searchables
     *
     * @return this
     */
    public function setSearchables(array $searchables)
    {
        $this->searchables = $searchables;
        return $this;
    }

    /**
     * Set Name Fields Searchable
     *
     * @param string $name
     *
     * @return this
     */
    public function setNameSearchable($name)
    {
        $this->nameSearchable = $name;
        return $this;
    }

    /**
     * Set Order BY
     *
     * @param string $orderBy
     *
     * @return this
     */
    public function setFieldOrderBy($orderBy)
    {
        $this->orderBy = strtolower($orderBy);
        return $this;
    }

    /**
     * Set Sorted By
     *
     * @param string $sortedBy
     *
     * @return this
     */
    public function setFieldSortedBy($sortedBy)
    {
        $sortedBy = strtolower($sortedBy);
        $this->sortedBy = in_array($sortedBy, ['asc', 'desc']) ? $sortedBy : 'asc';
        return $this;
    }

    /**
     * Apply criteria in query
     *
     * @param Builder $query
     *
     * @return mixed
     */

    public function apply($query, RepositoryInterface $repository = null)
    {
        if ($repository) {
            $this->searchables = array_merge($this->searchables, $repository->getFieldsSearchable());
        }

        if ($nameSearchable = config('repository.criteria.params.search')) {
            $this->setNameSearchable($nameSearchable);
        }

        if ($orderBy = config('repository.criteria.params.orderBy')) {
            $this->setFieldOrderBy($orderBy);
        }

        if ($sortedBy = config('repository.criteria.params.sortedBy')) {
            $this->setFieldSortedBy($sortedBy);
        }

        $model = $query;

        if ($query instanceof EloquentBuilder) {
            $model = $query->getModel();
        }

        if ($model instanceof Model) {
            $this->dates = $model->getDates();
            $this->table = $model->getTable().'.';
        }

        if ($model instanceof AbstractEntity) {
            $this->addColumns($model->columns());
        }

        foreach ($this->input as $key => $value) :
            // Parameter Grouping
            if ($value instanceof \Closure) {
                $query = $query->where($value);
                continue;
            }

            // Scope
            // eg: $query = $query->example($value);
            $methodScope = 'scope' . studly_case($key);
            if (is_object($model) && method_exists($model, $methodScope)) {
                $methodName = camel_case($key);
                $query = $query->{$methodName}($value);
                continue;
            }

            // Where Search
            if ($key === $this->nameSearchable) {
                $query = $this->whereSearch($query, $value);
                continue;
            }

            if (is_int($key)) {
                // Using A Raw Expression
                if ($value instanceof QueryExpression) {
                    $query = $query->whereRaw($value);
                }

                // Using String Format {field},{operator},{value}
                if (is_string($value) && preg_match('/^([a-zA-Z0-9_]+),(.+),(.+)$/', $value, $matches)) {
                    if (count($matches)==4) {
                        $value = array_splice($matches, 1, 3);
                    }
                }

                // Using Array com Operator
                // ex: ('field', '=', 'value') or
                //     ('field', 'value')
                if (is_array($value) && count($value)) {
                    $value = array_pad($value, 3, null);
                    list($field, $operator, $valor) = array_splice($value, 0, 3);
                    $query = $this->whereCriteria($query, $field, $operator, $valor);
                }

                continue;
            }

            $query = $this->whereCriteria($query, $key, '=', $value);

        endforeach;

        // Order By
        if ($this->orderBy && in_array($this->orderBy, $this->columns)) {
            $query = $query->orderBy($this->orderBy, $this->sortedBy);
        }

        return $query;
    }

    /**
     * Where
     *
     * @param Builder $query    Builder
     * @param unknown $key      Key
     * @param string  $operator String Operator
     * @param int     $value    Value
     * @throws InvalidArgumentException
     * @return mixed
     */
    protected function whereCriteria($query, $key, $operator = null, $value = null)
    {
        $validOperator = function ($operator, $value) {
            $operators = ['=', '<', '>', '<=', '>=', '<>', '!='];
            return in_array($operator, $operators);
        };

        if (! $validOperator($operator, $value)) {
            throw new InvalidArgumentException("Illegal operator and value combination.");
        }

        // Raw Expression with Bidding
        if (strpos($key, '?') !== false) {
            $query = $query->whereRaw($key, (array) $value);
            return $query;
        }

        $table = $this->table;
        $column = $key;
        if (preg_match('/^(.+\.)(.+)/', $key, $matches)) {
            $table = $matches[1];
            $column = $matches[2];
        }

        // Montagem Tabela com Coluns
        $key = $table.$column;

        // Attributes Valids
        if (in_array($column, $this->columns)) {
            if (is_null($value)) {
                if ($operator == '!=' || $operator == '<>') {
                    $query = $query->whereNotNull($key);
                } else {
                    $query = $query->whereNull($key);
                }
                return $query;
            }

            if (in_array($column, $this->dates)) {
                $query = $this->whereDate($query, $key, $operator, $value);
                return $query;
            }

            // Using Where In With An Array
            if (is_array($value)) {
                if ($operator == '!=' || $operator == '<>') {
                    $query = $query->whereNotIn($key, $value);
                } else {
                    $query = $query->whereIn($key, $value);
                }
                return $query;
            }

            // Busca Direta
            $query = $query->where($key, $operator, $value);
            return $query;

        } else {
            $query = $this->whereBetweenColumn($query, $key, $value);
            return $query;
        }

        return $query;
    }

    /**
     * Where Search
     *
     * @param Builder $query  Builder
     * @param string  $search String Search
     *
     * @return mixed
     */
    protected function whereSearch($query, $search)
    {
        $fieldsSearchable = $this->searchables;
        $query = $query->where(function ($query) use ($fieldsSearchable, $search) {
            foreach ($fieldsSearchable as $field => $condition) {
                if (is_numeric($field)) {
                    $field = $condition;
                    $condition = "=";
                }

                $condition  = trim(strtolower($condition));

                if (!empty($search)) {
                    $value = in_array($condition, ["like", "ilike"]) ? "%{$search}%" : $search;
                    $query->orWhere($this->table.$field, $condition, $value);
                }
            }
        });

        return $query;
    }

    /**
     * Where Date
     *
     * @param Builder $query    Builder
     * @param unknown $key      Key
     * @param string  $operator String Operator
     * @param int     $value    Value
     * @throws InvalidArgumentException
     * @return mixed
     */
    private function whereDate($query, $key, $operator, $value)
    {
        if (is_string($value)) {
            $value = str_replace("_", '', $value); // Caso vier com a mascara __/__/____
        }

        if (! empty($value)) {
            $date = asDateTime($value);

            if ($date instanceof Datetime) {
                $query = $query->whereRaw(
                    $this->dateFormatDb($query, $key, $operator, $date),
                    [$date->format('Y-m-d')]
                );
            } else {
                if ($operator == '!=' || $operator == '<>') {
                    $query = $query->whereNotNull($key);
                } else {
                    $query = $query->whereNull($key);
                }
            }
        }

        return $query;
    }

    /**
     *
     * @param Builder $query Builder Query
     * @param string  $key   String  Key
     * @param int     $value Value
     *
     *@return mixed
     */
    private function whereBetweenColumn($query, $key, $value)
    {
        $table = $this->table;
        $column = $key;
        if (preg_match('/^(.+\.)(.+)/', $key, $matches)) {
            $table = $matches[1];
            $column = $matches[2];
        }

        if (preg_match('/^(.+)(_ini|_fim)$/', $column, $match) && in_array($match[1], $this->columns)) {
            $field = $match[1];
            $operator = ($match[2]=='_ini')? '>=' : '<=';
            if (in_array($field, $this->dates)) {
                $query = $this->whereDate($query, $table.$field, $operator, $value);
            } else {
                $query = $query->where($table.$field, $operator, $value);
            }
        }

        return $query;
    }
    
    /**
     * Date Formate Database
     *
     * @param Builder  $query    Builder
     * @param unknown  $key      Column
     * @param string   $operator String Operator
     * @param DateTime $date     Date Time
     *
     * @return string
     */
    private function dateFormatDb($query, $key, $operator, DateTime $date)
    {
        if (!$this->grammar) {
            $this->grammar = $query->getQuery()->getGrammar();
        }

        $key = $this->grammar->wrap($key);

        $formatDb = sprintf("%s %s ?", $key, $operator);

        switch (true) {
            case $this->grammar instanceof \Illuminate\Database\Query\Grammars\MySqlGrammar:
                $formatDb = sprintf("DATE_FORMAT(%s, '%%Y-%%m-%%d') %s ?", $key, $operator);
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
