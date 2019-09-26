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
use Prettus\Repository\Events\RepositoryEntityDeleted;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars;
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
     * @return Model
     * @throws RepositoryException
     */
    public function makeModel()
    {
        parent::makeModel();
        return $this->model = $this->model->newQuery();
    }

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

        $model = $this->model;

        $this->resetModel();
        return $model;
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

        return new BuilderResultset($query);
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
     * ResultSet
     *
     * @param int $limit Integer Limit
     *
     * @return BuilderResultset
     */
    public function resultset($limit = null)
    {
        $query = $this->getQuery()->limit($limit);

        return new BuilderResultset($query);
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
        return $this->getQuery()->pluck($column, $key);
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
     * Random
     *
     * @return RepositoryInterface
     */
    public function random()
    {
        $grammar = $this->model->getConnection()->getQueryGrammar();

        switch (true) {
            case $grammar instanceof Grammars\MySqlGrammar:
            case $grammar instanceof Grammars\SqlServerGrammar:
                $random = 'RAND()';
                break;
            case $grammar instanceof Grammars\PostgresGrammar:
            case $grammar instanceof Grammars\SQLiteGrammar:
                $random = 'RANDOM()';
        }

        $this->model = $this->model->orderBy(new Expression($random));

        return $this;
    }

    /**
     * Count results of repository
     *
     * @param array $where
     * @param string $columns
     *
     * @return int
     */
    public function count(array $where = [], $columns = '*')
    {
        $this->whereInputCriteria($where);

        return $this->getQuery()->count($columns);
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
        $this->whereInputCriteria($input);

        return $this->getQuery()->max($field);
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
        $this->whereInputCriteria($input);

        return $this->getQuery()->min($field);
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
        $this->whereInputCriteria($input);

        return $this->getQuery()->sum($field);
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
        $this->whereInputCriteria($input);

        return $this->getQuery()->avg($field);
    }

    /**
     * Order Up
     *
     * @param Model  $model
     * @param string $field Field Order
     * @param array  $input Array Where
     *
     * @return boolean
     */
    public function orderUp($model, $field, array $input = [])
    {
        $input["{$field} <= ?"] = $model->{$field};
        $input["id != ?"] = $model->id;
        return $this->reorder($model, $field, $input, 'DESC');
    }

    /**
     * Order Top
     *
     * @param Model  $model
     * @param string $field Field Order
     * @param array  $input Array Where
     *
     * @return boolean
     */
    public function orderTop($model, $field, array $input = [])
    {
        return $this->reorder($model, $field, $input, 'ASC');
    }

    /**
     * Order Down
     *
     * @param Model  $model
     * @param string $field Field Order
     * @param array  $input Array Where
     *
     * @return boolean
     */
    public function orderDown($model, $field, array $input = [])
    {
        $input["{$field} >= ?"] = $model->{$field};
        $input["id != ?"] = $model->id;
        return $this->reorder($model, $field, $input, 'ASC');
    }

    /**
     * Order Bottom
     *
     * @param Model  $model
     * @param string $field Field Order
     * @param array  $input Array Where
     *
     * @return boolean
     */
    public function orderBottom($model, $field, array $input = [])
    {
        return $this->reorder($model, $field, $input, 'DESC');
    }

    /**
     * Reorder
     *
     * @param Model  $model
     * @param string $field Field Order
     * @param array  $input Array Where
     * @param string $sort  Sort
     *
     * @return boolean
     */
    protected function reorder($model, $field, array $input, $sort)
    {
        if (!$model->exists) {
            return false;
        }

        $order = $model->{$field};

        $anterior = $this->whereInputCriteria($input)->orderBy($field, $sort)->first();

        if ($anterior) {
            $model->{$field} = $anterior->{$field};
            $model->save();

            $anterior->{$field} = $order;
            $anterior->save();
        }

        event(new RepositoryEntityUpdated($this, $model));

        return true;
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
            $criteria = new InputCriteria($input);
            $this->model = $criteria->apply($this->model, $this);
        }

        return $this;
    }
    
    /**
     * Validar
     *
     * @param array  $attributes
     * @param string $action
     * @param string $id
     *
     * @return bool
     */
    public function validar(array $attributes, $action, $id = null)
    {
        $return = false;

        if (!is_null($this->validator)) {
            // we should pass data that has been casts by the model
            // to make sure data type are same because validator may need to use
            // this data to compare with data that fetch from database.
            $model = $this->model->newModelInstance()->fill($attributes);
            $attributes = array_merge($attributes, $model->toArray());

            $validator = $this->validator->with($attributes);

            if ($id) {
                $validator->setId($id);
            }

            $return = $validator->passesOrFail($action);
        }

        return $return;
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
        $this->validar($attributes, ValidatorInterface::RULE_CREATE);

        $model = $this->model->newModelInstance($attributes);
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

        $this->validar($attributes, ValidatorInterface::RULE_UPDATE, $id);

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
     * Delete Force a entity in repository by id
     *
     * @param $id
     *
     * @return int
     */
    public function deleteForce($id)
    {
        $this->applyScope();

        $temporarySkipPresenter = $this->skipPresenter;
        $this->skipPresenter(true);

        $model = $this->find($id);
        $originalModel = clone $model;

        $this->skipPresenter($temporarySkipPresenter);
        $this->resetModel();

        $deleted = $model->deleteForce();

        event(new RepositoryEntityDeleted($this, $originalModel));

        return $deleted;
    }

    /**
     * Delete multiple entities by given criteria.
     *
     * @param array $where
     *
     * @return boolean|null
     */
    public function deleteWhere(array $where)
    {
        $this->applyCriteria();
        $this->applyScope();

        $temporarySkipPresenter = $this->skipPresenter;
        $this->skipPresenter(true);

        $this->whereInputCriteria($where);

        $deleted = $this->model->delete();

        $model = $this->model instanceof Builder ? $this->model->getModel() : $this->model;
        event(new RepositoryEntityDeleted($this, $model));

        $this->skipPresenter($temporarySkipPresenter);
        $this->resetModel();

        return $deleted;
    }

    /**
     * Update multiple entities by given criteria.
     *
     * @param array $where
     *
     * @return boolean|null
     */
    public function updateWhere(array $attributes, array $where)
    {
        $this->applyCriteria();
        $this->applyScope();

        $temporarySkipPresenter = $this->skipPresenter;
        $this->skipPresenter(true);

        $this->whereInputCriteria($where);

        $updated = $this->model->update($attributes);

        $this->skipPresenter($temporarySkipPresenter);
        $this->resetModel();

        $model = $this->model instanceof Builder ? $this->model->getModel() : $this->model;
        event(new RepositoryEntityUpdated($this, $model));

        return $updated;
    }

    /**
     * Add a subquery join clause to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param  string  $as
     * @param  string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * @param  bool    $where
     * @return \Illuminate\Database\Query\Builder|static
     *
     * @throws \InvalidArgumentException
     */
    public function joinSub($query, $as, $first, $operator = null, $second = null, $type = 'inner', $where = false)
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        $expression = '('.$sql.') as '.$this->model->getGrammar()->wrap($as);
        $this->model->addBinding($bindings, 'join');

        return $this->join(new Expression($expression), $first, $operator, $second, $type, $where);
    }

    /**
     * Add a subquery left join to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param  string  $as
     * @param  string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function leftJoinSub($query, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($query, $as, $first, $operator, $second, 'left');
    }

    /**
     * Add a subquery right join to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param  string  $as
     * @param  string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function rightJoinSub($query, $as, $first, $operator = null, $second = null)
    {
        return $this->joinSub($query, $as, $first, $operator, $second, 'right');
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param  string  $method
     * @param  array   $parameters
     *
     * @return AbstractRepository
     *
     * @throws BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        $pattern = '/^(((where|orWhere).*)|select|limit|groupBy|join|leftJoin|rightJoin|crossJoin)$/';
        if (preg_match($pattern, $method)) {
            $this->model = call_user_func_array([$this->model, $method], $parameters);
            return $this;
        }

        $pattern = '/^(toSql|getBindings)$/';
        if (preg_match($pattern, $method)) {
            return call_user_func_array([$this->model, $method], $parameters);
        }

        $className = static::class;
        throw new BadMethodCallException("Call to undefined method {$className}::{$method}()");
    }
}
