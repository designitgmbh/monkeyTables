<?php

namespace Designitgmbh\MonkeyTables;

use Illuminate\Support\ServiceProvider;

class MonkeyTablesServiceProviderL4 extends ServiceProvider
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
        $this->package('designitgmbh/monkeyTables', 'monkeyTables', __DIR__);

        require __DIR__.'/Http/routes.php';
    }

    /**
     * Register the service provider.
     *
     * @codeCoverageIgnore
     * @return void
     */
    public function register()
    {
        $this->app->bind('monkeyTables',function($app){
            return new MonkeyTables($app);
        });
    }

}
