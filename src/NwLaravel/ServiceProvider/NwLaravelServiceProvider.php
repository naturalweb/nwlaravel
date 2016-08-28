<?php
namespace NwLaravel\ServiceProvider;

use Carbon\Carbon;
use NwLaravel\Socialite\OlxProvider;
use Laravel\Socialite\Facades\Socialite;
use NwLaravel\Validation\ValidatorResolver;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;
use NwLaravel\ActivityLog\ActivityManager;
use NwLaravel\ActivityLog\Commands\CleanLogCommand;

/**
 * Class NwLaravelServiceProvider
 */
class NwLaravelServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap any necessary services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootPublishConfig();

        $this->bootValidator();
        $this->bootTranslatorCarbon();
        $this->bootActivityLog();
        $this->bootOlxDriver();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerActivityLog();
    }

    /**
     * Boot Publish Config
     *
     * @return void
     */
    public function bootPublishConfig()
    {
        // Publish config files
        $this->publishes([
            __DIR__.'/../../config/nwlaravel.php' => config_path('nwlaravel.php'),
        ], 'config');

        // Merge config files
        $this->mergeConfigFrom(
            __DIR__.'/../../config/nwlaravel.php',
            'nwlaravel'
        );
    }

    /**
     * Boot Translator Carbon
     *
     * @return void
     */
    protected function bootTranslatorCarbon()
    {
        $locale = $this->app['config']->get('app.locale');
        $path = __DIR__.'/../../resources/lang/'.$locale.'/carbon.php';

        if (file_exists($path)) {
            $translator = new Translator($locale);
            $translator->addLoader('array', new ArrayLoader());
            $translator->addResource('array', require $path, $locale);
            Carbon::setTranslator($translator);
        }
    }

    /**
     * Boot Validator
     *
     * @return void
     */
    protected function bootValidator()
    {
        $this->app['validator']->resolver(function ($translator, $data, $rules, $messages, $customAttributes) {
            return new ValidatorResolver($translator, $data, $rules, $messages, $customAttributes);
        });
    }

    /**
     * Boot Activity Log
     *
     * @return void
     */
    protected function bootActivityLog()
    {
        // Publish migration
        $files = glob(database_path('/migrations/*_create_activity_log_table.php'));
        if (count($files) == 0) {
            $timestamp = date('Y_m_d_His', time());
            $this->publishes([
                __DIR__ . "/../../migrations/create_activity_log_table.stub" => database_path("/migrations/{$timestamp}_create_activity_log_table.php"),
            ], 'migrations');
        }
    }

    /**
     * Registero Activity Log
     *
     * @return void
     */
    protected function registerActivityLog()
    {
        $this->app->singleton('nwlaravel.activity', function ($app) {
            $handler = $app->make($app['config']->get('nwlaravel.activity.handler'));
            return new ActivityManager($handler, $app['auth'], $app['config']);
        });

        $this->app->singleton('nwlaravel.command.activity:clean', function ($app) {
            return new CleanLogCommand($app['nwlaravel.activity']);
        });

        $this->app->alias('nwlaravel.activity', ActivityManager::class);
        $this->commands(['nwlaravel.command.activity:clean']);
    }

    /**
     * Boot OlxDriver
     *
     * @return void
     */
    protected function bootOlxDriver()
    {
        if (class_exists(Socialite::class)) {
            Socialite::extend('olx', function ($app) {
                $config = $app['config']['services.olx'];
                return new OlxProvider(
                    $app['request'],
                    $config['client_id'],
                    $config['client_secret'],
                    $config['redirect']
                );
            });
        }
    }
}
