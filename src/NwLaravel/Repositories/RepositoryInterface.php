<?php
namespace NwLaravel\Repositories;

use Prettus\Repository\Contracts\RepositoryInterface as BaseInterface;

/**
 * Interface RepositoryInterface
 */
interface RepositoryInterface extends BaseInterface
{
    /**
     * Search All
     *
     * @param array  $input         Array Input
     * @param string $orderBy       String Order By
     * @param int    $limit         Integer Limit
     * @param bool   $skipPresenter Boolean Skip Presenter
     *
     * @return NwLaravel\Resultset\BuilderResultset
     */
    public function searchAll(array $input, $orderBy = '', $limit = null, $skipPresenter = true);

    /**
     * Search
     *
     * @param array  $input   Array Input
     * @param string $orderBy String Order By
     * @param int    $limit   Integer Limit
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function search(array $input, $orderBy = '', $limit = null);

    /**
     * Get an array with the values of a given column.
     *
     * @param string $column String Column
     * @param string $key    String Key
     *
     * @return \Illuminate\Support\Collection
     */
    public function pluck($column, $key = null);

    /**
     * Add an "order by" clause to the query.
     *
     * @param string $column    String Column
     * @param string $direction String Direction
     *
     * @return RepositoryInterface
     */
    public function orderBy($column, $direction = 'asc');

    /**
     * Count
     *
     * @param array $input Array Input
     *
     * @return int
     */
    public function count(array $input = array());

    /**
     * Max
     *
     * @param mixed $field Mixed Field
     * @param array $input Array Input
     *
     * @return mixed
     */
    public function max($field, array $input = array());

    /**
     * Min
     *
     * @param mixed $field Mixed Field
     * @param array $input Array Input
     *
     * @return mixed
     */
    public function min($field, array $input = array());

    /**
     * Sum
     *
     * @param mixed $field Mixed Field
     * @param array $input Array Input
     *
     * @return float
     */
    public function sum($field, array $input = array());

    /**
     * Average
     *
     * @param mixed $field Mixed Field
     * @param array $input Array Input
     *
     * @return int
     */
    public function avg($field, array $input = array());

    /**
     * Order Up
     *
     * @param Model  $model
     * @param string $field Field Order
     * @param array  $input Array Where
     *
     * @return boolean
     */
    public function orderUp($model, $field, array $input = []);

    /**
     * Order Down
     *
     * @param Model  $model
     * @param string $field Field Order
     * @param array  $input Array Where
     *
     * @return boolean
     */
    public function orderDown($model, $field, array $input = []);

    /**
     * Where InputCriteria
     *
     * @param array $input Array Input
     *
     * @return RepositoryInterface
     */
    public function whereInputCriteria(array $input = array());

    /**
     * Get Query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getQuery();
}
