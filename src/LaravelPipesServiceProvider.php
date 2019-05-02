<?php

namespace Mshule\LaravelPipes;

use Illuminate\Support\ServiceProvider;
use Mshule\LaravelPipes\Contracts\Registrar;

class LaravelPipesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        $this->app->singleton('piper', function ($app) {
            return new Piper($app);
        });

        $this->app->singleton(Kernel::class, function ($app) {
            return new Kernel($app, resolve('piper'));
        });

        $this->app->alias('piper', \Mshule\LaravelPipes\Piper::class);
        $this->app->alias('piper', \Mshule\LaravelPipes\Contracts\Registrar::class);
        // $this->app->singleton(Registrar::class, function ($app) {
        //     return $app['piper'];
        // });

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
