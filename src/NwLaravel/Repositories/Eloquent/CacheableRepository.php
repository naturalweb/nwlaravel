<?php
namespace NwLaravel\Repositories\Eloquent;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Prettus\Repository\Contracts\CriteriaInterface;
use ReflectionObject;
use Exception;

/**
 * Class CacheableRepository
 */
trait CacheableRepository
{
    /**
     * @var CacheRepository
     */
    protected $cacheRepository = null;

    /**
     * @var boolean
     */
    protected $cacheSkip = false;

    /**
     * Set Cache Repository
     *
     * @param CacheRepository $repository
     *
     * @return $this
     */
    public function setCacheRepository(CacheRepository $repository)
    {
        $this->cacheRepository = $repository;

        return $this;
    }

    /**
     * Return instance of Cache Repository
     *
     * @return CacheRepository
     */
    public function getCacheRepository()
    {
        if (is_null($this->cacheRepository)) {
            $this->cacheRepository = app(config('repository.cache.repository', 'cache'));
        }

        return $this->cacheRepository;
    }

    /**
     * CacheTags
     *
     * @param mixed $tags
     *
     * @return \Illuminate\Cache\TaggedCache
     */
    public function cacheTags($tags = null)
    {
        $tags = empty($tags) ? get_called_class() : $tags;

        return $this->getCacheRepository()->tags($tags);
    }

    /**
     * Skip Cache
     *
     * @param bool $status
     *
     * @return $this
     */
    public function skipCache($status = true)
    {
        $this->cacheSkip = $status;

        return $this;
    }

    /**
     * Is Skipped Cache
     *
     * @return bool
     */
    public function isSkippedCache()
    {
        $skipped = isset($this->cacheSkip) ? $this->cacheSkip : false;
        $request = app('Illuminate\Http\Request');
        $skipCacheParam = config('repository.cache.params.skipCache', 'skipCache');

        if ($request->has($skipCacheParam) && $request->get($skipCacheParam)) {
            $skipped = true;
        }

        return $skipped;
    }

    /**
     * Allowed Cache
     *
     * @param string $method
     *
     * @return bool
     */
    protected function allowedCache($method)
    {
        $cacheEnabled = config('repository.cache.enabled', true);

        if (!$cacheEnabled) {
            return false;
        }

        $cacheOnly = isset($this->cacheOnly) ? $this->cacheOnly : config('repository.cache.allowed.only', null);
        $cacheExcept = isset($this->cacheExcept) ? $this->cacheExcept : config('repository.cache.allowed.except', null);

        if (is_array($cacheOnly)) {
            return in_array($method, $cacheOnly);
        }

        if (is_array($cacheExcept)) {
            return !in_array($method, $cacheExcept);
        }

        if (is_null($cacheOnly) && is_null($cacheExcept)) {
            return true;
        }

        return false;
    }

    /**
     * Get Cache key for the method
     *
     * @param string $method
     * @param array  $args
     *
     * @return string
     */
    public function getCacheKey($method, $args = null)
    {
        $request = app('Illuminate\Http\Request');
        $args = serialize($args);
        $criteria = $this->serializeCriteria();
        $sql = $this->model->toSql();
        $bindings = serialize($this->model->getBindings());
        $skipPresenter = $this->skipPresenter ? '-skipPresenter-' : '';
        
        return sprintf('%s@%s-%s', get_called_class(), $method, md5($args . $criteria . $bindings . $skipPresenter . $request->fullUrl()));
    }

    /**
     * Serialize the criteria making sure the Closures are taken care of.
     *
     * @return string
     */
    protected function serializeCriteria()
    {
        try {
            return serialize($this->getCriteria());
        } catch (Exception $e) {
            return serialize($this->getCriteria()->map(function ($criterion) {
                return $this->serializeCriterion($criterion);
            }));
        }
    }

    /**
     * Serialize single criterion with customized serialization of Closures.
     *
     * @param  \Prettus\Repository\Contracts\CriteriaInterface $criterion
     *
     * @return \Prettus\Repository\Contracts\CriteriaInterface|array
     *
     * @throws \Exception
     */
    protected function serializeCriterion($criterion)
    {
        try {
            serialize($criterion);

            return $criterion;
        } catch (Exception $e) {
            // We want to take care of the closure serialization errors,
            // other than that we will simply re-throw the exception.
            if ($e->getMessage() !== "Serialization of 'Closure' is not allowed") {
                throw $e;
            }

            $reflection = new ReflectionObject($criterion);

            return [
                'hash' => md5((string) $reflection),
                'properties' => $reflection->getProperties(),
            ];
        }
    }

    /**
     * Get cache minutes
     *
     * @return int
     */
    public function getCacheMinutes()
    {
        $cacheMinutes = isset($this->cacheMinutes) ? $this->cacheMinutes : config('repository.cache.minutes', 30);

        return $cacheMinutes;
    }

    /**
     * call Cache
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function callCache($method, array $args)
    {
        if (!$this->allowedCache($method) || $this->isSkippedCache()) {
            return call_user_func_array(['parent', $method], $args);
        }

        $key = $this->getCacheKey($method, $args);
        $minutes = $this->getCacheMinutes();
        $value = $this->cacheTags()->remember($key, $minutes, function () use ($method, $args) {
            return call_user_func_array(['parent', $method], $args);
        });

        return $value;
    }

    /**
     * Retrieve all data of repository
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function all($columns = ['*'])
    {
        return $this->callCache(__FUNCTION__, func_get_args());
    }

    /**
     * Retrieve all data of repository, paginated
     *
     * @param null $limit
     * @param array $columns
     * @param string $method
     *
     * @return mixed
     */
    public function paginate($limit = null, $columns = ['*'], $method = "paginate")
    {
        return $this->callCache(__FUNCTION__, func_get_args());
    }

    /**
     * Find data by id
     *
     * @param int   $id
     * @param array $columns
     *
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        return $this->callCache(__FUNCTION__, func_get_args());
    }

    /**
     * Find data by field and value
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $columns
     *
     * @return mixed
     */
    public function findByField($field, $value = null, $columns = ['*'])
    {
        return $this->callCache(__FUNCTION__, func_get_args());
    }

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     *
     * @return mixed
     */
    public function findWhere(array $where, $columns = ['*'])
    {
        return $this->callCache(__FUNCTION__, func_get_args());
    }

    /**
     * Find data by Criteria
     *
     * @param CriteriaInterface $criteria
     *
     * @return mixed
     */
    public function getByCriteria(CriteriaInterface $criteria)
    {
        return $this->callCache(__FUNCTION__, func_get_args());
    }

    /**
     * Pluck
     *
     * @param string $column
     * @param array  $key
     *
     * @return array
     */
    public function pluck($column, $key = null)
    {
        return $this->callCache(__FUNCTION__, func_get_args());
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
        return $this->callCache(__FUNCTION__, func_get_args());
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
        return $this->callCache(__FUNCTION__, func_get_args());
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
        return $this->callCache(__FUNCTION__, func_get_args());
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
        return $this->callCache(__FUNCTION__, func_get_args());
    }

    /**
     * Average
     *
     * @param mixed $field Mixed Field
     * @param array $input Array Input
     *
     * @return float
     */
    public function avg($field, array $input = array())
    {
        return $this->callCache(__FUNCTION__, func_get_args());
    }
}
