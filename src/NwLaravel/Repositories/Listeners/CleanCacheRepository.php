<?php
namespace NwLaravel\Repositories\Listeners;

use \Exception;
use Illuminate\Support\Facades\Log;
use Prettus\Repository\Events\RepositoryEventBase;

/**
 * Class CleanCacheRepository
 */
class CleanCacheRepository
{

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache = null;

    /**
     * @var \Prettus\Repository\Contracts\RepositoryInterface;
     */
    protected $repository = null;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model = null;

    /**
     * @var string
     */
    protected $action = null;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->cache = app(config('repository.cache.repository', 'cache'));
    }

    /**
     * Handle
     *
     * @param RepositoryEventBase $event
     */
    public function handle(RepositoryEventBase $event)
    {
        try {
            $cleanEnabled = config("repository.cache.clean.enabled", true);

            if ($cleanEnabled) {
                $this->repository = $event->getRepository();
                $this->model = $event->getModel();
                $this->action = $event->getAction();

                if (config("repository.cache.clean.on.{$this->action}", true)) {
                    $tag = get_class($this->repository);
                    $this->cache->tags($tag)->flush();
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
