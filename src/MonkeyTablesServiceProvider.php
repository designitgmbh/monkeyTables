<?php

namespace Designitgmbh\MonkeyTables;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;


if(!function_exists('public_path'))
{
        /**
        * Return the path to public dir
        * @param null $path
        * @return string
        */
        function public_path($path=null)
        {
                return rtrim(app()->basePath('public/'.$path), '/');
        }
}

class MonkeyTablesServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // use this if your package has views
        $this->loadViewsFrom(realpath(__DIR__.'/resources/views'), 'monkeyTables');
        
        // use this if your package has routes
        //$this->setupRoutes($this->app->router);
        
        // use this if your package needs a config file
        // $this->publishes([
        //         __DIR__.'/config/config.php' => config_path('monkeyTables.php'),
        // ]);
        
        // use the vendor configuration file as fallback
        // $this->mergeConfigFrom(
        //     __DIR__.'/config/config.php', 'monkeyTables'
        // );

        // use this if your package has assets
            // todo, add assets
                //js
                //css/less
        $this->publishes([
            __DIR__.'/resources/public' => public_path('monkeyTables'),
        ], 'public');
    }

    /**
     * Define the routes for the application.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function setupRoutes(Router $router)
    {
        $router->group(['namespace' => 'Designitgmbh\MonkeyTables\Http\Controllers'], function($router)
        {
            require __DIR__.'/Http/routes.php';
        });
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerMonkeyTables();
        
        // use this if your package has a config file
        // config([
        //         'config/monkeyTables.php',
        // ]);
    }

    private function registerMonkeyTables()
    {
        $this->app->bind('monkeyTables',function($app){
            return new MonkeyTables($app);
        });
    }
}
