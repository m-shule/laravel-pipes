<?php

namespace Mshule\LaravelPipes;

use Illuminate\Routing\Pipeline;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Mshule\LaravelPipes\Exceptions\NotFoundPipeException;

class Kernel extends HttpKernel
{
    /**
     * The piper instance.
     *
     * @var \Mshule\LaravelPipes\Piper
     */
    protected $piper;

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'pipe' => [
            \Mshule\LaravelPipes\Http\Middleware\SubstituteBindings::class,
        ]
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'bindings' => \Mshule\LaravelPipes\Http\Middleware\SubstituteBindings::class,
    ];

    /**
     * The priority-sorted list of middleware.
     *
     * Forces non-global middleware to always be in the given order.
     *
     * @var array
     */
    protected $middlewarePriority = [
        \Mshule\LaravelPipes\Http\Middleware\SubstituteBindings::class,
    ];

    /**
     * Create a new PipeRequestHandler instance.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param \Mshule\LaravelPipes\Piper                   $piper
     */
    public function __construct(Application $app, Piper $piper)
    {
        $this->piper = $piper;

        parent::__construct($app, $piper);
    }

    /**
     * Handle an incoming HTTP request.
     *
     * @param \Mshule\LaravelPipes\Request $request
     * @return \Illuminate\Http\Response
     */
    public function handle($request)
    {
        try {
            $response = $this->sendRequestThroughPipes($request);
        } catch (NotFoundPipeException $e) {
            throw new NotFoundPipeException($request);
        }

        return Response::from($response);
    }

    /**
     * Send the given request through the middleware / pipes.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    protected function sendRequestThroughPipes($request)
    {
        $this->bootstrap();

        return (new Pipeline($this->app))
                    ->send($request)
                    ->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
                    ->then($this->dispatchToPiper());
    }

    /**
     * Get the pipe dispatcher callback.
     *
     * @return \Closure
     */
    protected function dispatchToPiper()
    {
        return function ($request) {
            return $this->piper->dispatch($request);
        };
    }

    /**
     * Gather the pipe middleware for the given request.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    protected function gatherRouteMiddleware($request)
    {
        if ($pipe = $request->route()) {
            return $this->piper->gatherRouteMiddleware($pipe);
        }

        return [];
    }
}
