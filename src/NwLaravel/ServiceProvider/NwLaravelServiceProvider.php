<?php
namespace NwLaravel\ServiceProvider;

use Carbon\Carbon;
use NwLaravel\Socialite\OlxProvider;
use Laravel\Socialite\Facades\Socialite;
use NwLaravel\Validation\ValidatorResolver;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;

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
        // Publish config files
        $this->publishes([
            __DIR__.'/../../config/nwlaravel.php' => config_path('nwlaravel.php'),
        ]);

        $this->bootValidator();
        $this->bootTranslatorCarbon();
        $this->bootOlxDriver();
    }

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

    protected function bootValidator()
    {
        $this->app['validator']->resolver(function ($translator, $data, $rules, $messages, $customAttributes) {
            return new ValidatorResolver($translator, $data, $rules, $messages, $customAttributes);
        });
    }

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
    
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
