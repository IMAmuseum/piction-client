<?php

namespace Imamuseum\PictionClient;

use Illuminate\Support\ServiceProvider;

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
            __DIR__.'/../config/piction.php' => config_path('piction.php'),
        ]);
    }
}
