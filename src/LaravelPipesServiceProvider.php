<?php

namespace Mshule\LaravelPipes;

use Illuminate\Support\ServiceProvider;
use Mshule\LaravelPipes\Contracts\ControllerDispatcher as ControllerDispatcherContract;

class LaravelPipesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        $this->app->singleton('piper', function ($app) {
            return new Piper();
        });

        $this->app->singleton(PipeRequestHandler::class, function ($app) {
            return new PipeRequestHandler($app, resolve('piper'));
        });

        $this->app->singleton(ControllerDispatcherContract::class, function ($app) {
            return new ControllerDispatcher($app);
        });

        $this->publishes([
            __DIR__ . '/../config/pipes.php' => config_path('pipes.php'),
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pipes.php',
            'pipes'
        );

        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }
}
