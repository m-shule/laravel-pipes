<?php

namespace Mshule\LaravelPipes;

use Mshule\LaravelPipes\Facades\Pipe;
use Illuminate\Support\ServiceProvider;

class LaravelPipesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        $this->performBindings();

        $this->publishes([
            __DIR__.'/../config/pipes.php' => config_path('pipes.php'),
        ]);
    }

    /**
     * Perform all needed bindings.
     */
    protected function performBindings()
    {
        $this->app->singleton('piper', function ($app) {
            return new Piper($app);
        });

        $this->app->singleton(Kernel::class, function ($app) {
            return new Kernel($app, resolve('piper'));
        });

        $this->app->alias('piper', \Mshule\LaravelPipes\Piper::class);

        $this->app->bind('pipe_any', function () {
            return '*any*';
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/pipes.php',
            'pipes'
        );

        $this->loadRoutes();
    }

    /**
     * Load all routes for using pipes.
     */
    protected function loadRoutes()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        if (config('pipes.load_routes_file')) {
            $this->mapPipeRoutes();
        }
    }

    /**
     * Define the "pipe" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapPipeRoutes()
    {
        Pipe::middleware('pipe')
             ->namespace(config('pipes.namespace'))
             ->group(base_path('routes/pipes.php'));
    }
}
