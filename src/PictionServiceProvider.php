<?php

namespace Imamuseum\PictionClient;

use Illuminate\Support\ServiceProvider;

use Imamuseum\PictionClient\Piction;

class PictionServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->publishes([
            __DIR__.'/config/piction-client.php' => config_path('piction-client.php'),
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['piction'];
    }
}