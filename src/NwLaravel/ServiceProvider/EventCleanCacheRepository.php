<?php
namespace NwLaravel\ServiceProvider;

use Illuminate\Support\ServiceProvider;

class EventCleanCacheRepository extends ServiceProvider
{

    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'Prettus\Repository\Events\RepositoryEntityCreated' => [
            'NwLaravel\Repositories\Listeners\CleanCacheRepository'
        ],
        'Prettus\Repository\Events\RepositoryEntityUpdated' => [
            'NwLaravel\Repositories\Listeners\CleanCacheRepository'
        ],
        'Prettus\Repository\Events\RepositoryEntityDeleted' => [
            'NwLaravel\Repositories\Listeners\CleanCacheRepository'
        ]
    ];

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function boot()
    {
        $events = app('events');

        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                $events->listen($event, $listener);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        //
    }
}
