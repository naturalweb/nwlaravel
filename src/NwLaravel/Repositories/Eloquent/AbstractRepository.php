<?php

namespace NwLaravel\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Prettus\Repository\Eloquent\BaseRepository;
use NwLaravel\Repositories\RepositoryInterface;
use NwLaravel\Repositories\Criterias\InputCriteria;
use NwLaravel\Resultset\BuilderResultset;
use Prettus\Validator\Contracts\ValidatorInterface;
use Prettus\Repository\Events\RepositoryEntityCreated;
use Prettus\Repository\Events\RepositoryEntityUpdated;
use BadMethodCallException;
use RuntimeException;

/**
 * Class AbstractRepository
 *
 * @abstract
 */
abstract class AbstractRepository extends BaseRepository implements RepositoryInterface
{
    /**
     * @var string
     */
    protected $orderBy;

    /**
     * @var bool
     */
    protected $skipPresenter = true;

    /**
     * Reset Model
     *
     * @return AbstractRepository
     * @throws RepositoryException
     */
    public function resetModel()
    {
        parent::resetModel();
        return $this;
    }

    /**
     * Get Query
     *
     * @return Builder
     */
    public function getQuery()
    {
        $this->applyCriteria();
        $this->applyScope();

        return ($this->model instanceof Builder) ? $this->model : $this->model->newQuery();
    }

    /**
     * Search All
     *
     * @param array  $input         Array Imput
     * @param string $orderBy       String Order By
     * @param int    $limit         Integer Limit
     * @param bool   $skipPresenter Boolean Skip Presenter
     *
     * @return BuilderResultset
     */
    public function searchAll(array $input, $orderBy = '', $limit = null, $skipPresenter = true)
    {
        $orderBy = $orderBy?:$this->orderBy;

        $query = $this
            ->whereInputCriteria($input)
            ->orderBy($orderBy)
            ->skipPresenter($skipPresenter)
            ->getQuery()
            ->limit($limit);

        $this->resetModel();
        return app(BuilderResultset::class, [$query]);
    }

    /**
     * Search Paginator
     *
     * @param array    $input         Array Input
     * @param string   $orderBy       String Order By
     * @param int|null $limit         Integer Limit
     * @param bool     $skipPresenter Boolean Skip Presenter
     *
     * @return Paginator
     */
    public function search(array $input, $orderBy = '', $limit = null, $skipPresenter = true)
    {
        $orderBy = $orderBy?:$this->orderBy;

        return $this
            ->whereInputCriteria($input)
            ->orderBy($orderBy)
            ->skipPresenter($skipPresenter)
            ->paginate($limit);
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param  string $column String Column
     * @param  string $key    String Key
     *
     * @return \Illuminate\Support\Collection
     */
    public function pluck($column, $key = null)
    {
        $this->applyCriteria();
        $this->applyScope();

        $lists = $this->model->pluck($column, $key);

        $this->resetModel();
        return $lists;
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param  string $columns   String Columns
     * @param  string $direction String Direction
     *
     * @return RepositoryInterface
     */
    public function orderBy($columns, $direction = 'asc')
    {
        if (!empty($columns)) {
            $columns = explode(',', $columns);
            foreach ($columns as $key => $column) {
                $column = explode(' ', $column);
                $column = array_filter($column);
                $column = array_pad($column, 2, '');
                list($field, $sort) = array_values($column);
                if (!empty($sort)) {
                    $direction = $sort;
                }
                $direction = strtoupper($direction);
                $direction = in_array($direction, ['ASC', 'DESC']) ? $direction : 'ASC';
                $this->model = $this->model->orderBy($field, $direction);
            }
        }

        return $this;
    }
    
    /**
     * Count
     *
     * @param array $input Array Input
     *
     * @return int
     */
    public function count(array $input = array())
    {
        $this->applyCriteria();
        $this->applyScope();
        
        $this->whereInputCriteria($input);
        
        $count = $this->model->count();
        
        $this->resetModel();
        return $count;
    }
    
    /**
     * Max
     *
     * @param mixed $field Mixed Field
     * @param array $input Array Input
     *
     * @return mixed
     */
    public function max($field, array $input = array())
    {
        $this->applyCriteria();
        $this->applyScope();
    
        $this->whereInputCriteria($input);
    
        $max = $this->model->max($field);
    
        $this->resetModel();
        return $max;
    }

    /**
     * Min
     *
     * @param mixed $field Mixed Field
     * @param array $input Array Input
     *
     * @return mixed
     */
    public function min($field, array $input = array())
    {
        $this->applyCriteria();
        $this->applyScope();
    
        $this->whereInputCriteria($input);
    
        $max = $this->model->min($field);
    
        $this->resetModel();
        return $max;
    }

    /**
     * Sum
     *
     * @param mixed $field Mixed Field
     * @param array $input Array Input
     *
     * @return float
     */
    public function sum($field, array $input = array())
    {
        $this->applyCriteria();
        $this->applyScope();
    
        $this->whereInputCriteria($input);
    
        $max = $this->model->sum($field);
    
        $this->resetModel();
        return $max;
    }

    /**
     * Average
     *
     * @param mixed $field Mixed Field
     * @param array $input Array Input
     *
     * @return int
     */
    public function avg($field, array $input = array())
    {
        $this->applyCriteria();
        $this->applyScope();
    
        $this->whereInputCriteria($input);
    
        $avg = $this->model->avg($field);
    
        $this->resetModel();
        return $avg;
    }

    /**
     * Reorder
     *
     * @param string $field Field Order
     *
     * @return boolean
     */
    public function reorder($field, $input = null)
    {
        $self = $this;
        $conn = $this->model->getConnection();

        $reorder = function ($statement, $value) use ($self, $conn, $input, $field) {
            $conn->statement($statement);
            $data = [$field => $conn->raw($value)];

            return $self->whereInputCriteria($input)
                        ->orderBy($field)
                        ->getQuery()
                        ->update($data);
        };

        $return = false;

        switch (true) {
            case $conn instanceof \Illuminate\Database\MySqlConnection:
                $statement = "SET @rownum := 0";
                $value = "(@rownum := @rownum+1)";
                $return = $reorder($statement, $value);
                break;

            case $conn instanceof \Illuminate\Database\PostgresConnection:
                $statement = "CREATE TEMPORARY SEQUENCE rownum_seq";
                $value = "NETVAL('rownum_seq')";
                $return = $reorder($statement, $value);
                break;

            case $conn instanceof \Illuminate\Database\SqlServerConnection:
                $statement = "DECLARE @rownum int; SET @rownum = 0";
                $value = "(@rownum = @rownum+1)";
                $return = $reorder($statement, $value);
                break;
        }

        if ($return) {
            return $return;
        }

        throw new RuntimeException(sprintf("Reorder not valid for connection (%s)", get_class($conn)));
    }

    /**
     * Where InputCriteria
     *
     * @param array $input Array Input
     *
     * @return RepositoryInterface
     */
    public function whereInputCriteria(array $input = array())
    {
        if (count($input)) {
            $criteria = app(InputCriteria::class, [$input]);
            $this->model = $criteria->apply($this->model, $this);
        }

        return $this;
    }
    
    /**
     * Save a new model in repository
     *
     * @throws ValidatorException
     * @param array $attributes Array Attributes
     * @return mixed
     */
    public function create(array $attributes)
    {
        if (!is_null($this->validator)) {
            // we should pass data that has been casts by the model
            // to make sure data type are same because validator may need to use
            // this data to compare with data that fetch from database.
            $model = $this->model->newInstance()->forceFill($attributes);
            $attributes = array_merge($attributes, $model->toArray());

            $this->validator->with($attributes)->passesOrFail(ValidatorInterface::RULE_CREATE);
        }

        $model = $this->model->newInstance($attributes);
        $model->save();
        $this->resetModel();

        event(new RepositoryEntityCreated($this, $model));

        return $this->parserResult($model);
    }

    /**
     * Update a model in repository by id
     *
     * @throws ValidatorException
     * @param array $attributes Array Attributes
     * @param int   $id         Integer Id
     * @return mixed
     */
    public function update(array $attributes, $id)
    {
        $this->applyScope();

        if (!is_null($this->validator)) {
            // we should pass data that has been casts by the model
            // to make sure data type are same because validator may need to use
            // this data to compare with data that fetch from database.
            $model = $this->model->newInstance()->forceFill($attributes);
            $attributes = array_merge($attributes, $model->toArray());

            $this->validator->with($attributes)->setId($id)->passesOrFail(ValidatorInterface::RULE_UPDATE);
        }

        $temporarySkipPresenter = $this->skipPresenter;

        $this->skipPresenter(true);

        $model = $this->model->findOrFail($id);
        $model->fill($attributes);
        $model->save();

        $this->skipPresenter($temporarySkipPresenter);
        $this->resetModel();

        event(new RepositoryEntityUpdated($this, $model));

        return $this->parserResult($model);
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        $pattern = '/^(((where|orWhere).*)|limit|groupBy|join|leftJoin|rightJoin|crossJoin)$/';
        if (preg_match($pattern, $method)) {
            $this->model = call_user_func_array([$this->model, $method], $parameters);
            return $this;
        }

        $className = static::class;
        throw new BadMethodCallException("Call to undefined method {$className}::{$method}()");
    }
}
