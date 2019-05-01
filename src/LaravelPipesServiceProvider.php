<?php

namespace Mshule\LaravelPipes;

use Illuminate\Support\ServiceProvider;

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
