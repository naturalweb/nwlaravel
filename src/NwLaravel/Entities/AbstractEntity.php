<?php

namespace NwLaravel\Entities;

use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\Presentable;
use Prettus\Repository\Contracts\PresenterInterface;
use NwLaravel\Repositories\Criterias\InputCriteria;
use Illuminate\Support\Arr;

/**
 * Class AbstractEntity
 *
 * @abstract
 */
abstract class AbstractEntity extends Model implements Presentable
{
    /**
     * @var array
     */
    protected $columns;

    /**
     * FEITO O PULL REQUEST # ATE SER SOLICITADO
     * INICIO: Prettus\Repository\Traits\PresentableTrait
     */
    /**
     * @var PresenterInterface
     */
    protected $presenter = null;

    /**
     * @param \Prettus\Repository\Contracts\PresenterInterface $presenter
     *
     * @return $this
     */
    public function setPresenter(PresenterInterface $presenter)
    {
        $this->presenter = $presenter;

        return $this;
    }

    /**
     * @param      $key
     * @param null $default
     *
     * @return mixed|null
     */
    public function present($key, $default = null)
    {
        if ($this->hasPresenter()) {
            $data = $this->presenter()['data'];

            return Arr::get($data, $key, $default);
        }

        return $default;
    }

    /**
     * @return bool
     */
    public function hasPresenter()
    {
        return isset($this->presenter) && $this->presenter instanceof PresenterInterface;
    }

    /**
     * @return $this|mixed
     */
    public function presenter()
    {
        if ($this->hasPresenter()) {
            return $this->presenter->present($this);
        }

        return $this;
    }
    /**
     * FIM Prettus\Repository\Traits\PresentableTrait
     */

    /**
     * Set a given attribute on the model.
     *
     * @param string $key   String Key
     * @param mixed  $value Mixed Value
     *
     * @return void
     */
    public function setAttribute($key, $value)
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if (empty($value) && $value != "0") {
            $value = null;
        }

        parent::setAttribute($key, $value);
    }

    /**
     * Get an attribute from the $attributes array.
     *
     * @param string $key String Key
     *
     * @return mixed
     */
    public function getRawAttribute($key)
    {
        return $this->getAttributeFromArray($key);
    }

    /**
     * Lista de Colunas
     *
     * @return array
     */
    public function columns()
    {
        if (is_null($this->columns)) {
            $table = $this->getTable();
            $this->columns = $this->getConnection()->getSchemaBuilder()->getColumnListing($table);
            $this->columns = array_map('strtolower', $this->columns);
        }

        // MongoDB
        if (array_search('_id', $this->columns)===false) {
            $this->columns[] = '_id';
        }

        if (array_search('id', $this->columns)===false) {
            $this->columns[] = 'id';
        }

        return $this->columns;
    }

    /**
     * Is Column in Table
     *
     * @param string $key String Key
     *
     * @return bool
     */
    public function isColumn($key)
    {
        return in_array(strtolower($key), $this->columns());
    }

    /**
     * Retorna o ultimo id registrado
     *
     * @return int
     */
    public function lastInsertId()
    {
        return $this->getConnection()->getPdo()->lastInsertId();
    }

    /**
     * Convert a DateTime to a storable string.
     *
     * @param \DateTime|int $value Date Time\Integer Value
     * @return string
     */
    public function fromDateTime($value)
    {
        $value = fromDateTime($value);
        if (empty($value)) {
            return $value;
        }
        return parent::fromDateTime($value);
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param mixed $value Mixed Value
     * @return \Carbon\Carbon
     */
    public function asDateTime($value)
    {
        $value = asDateTime($value);
        if (empty($value)) {
            return $value;
        }

        return parent::asDateTime($value);
    }

    /**
     * Scope Where Criteria
     *
     * @param unknown $query      Query
     * @param array $input      Input
     *
     * @return mixed
     */
    public function scopeWhereCriteria($query, array $input)
    {
        $criteria = app(InputCriteria::class, [$input]);
        return $criteria->apply($query);
    }
}
