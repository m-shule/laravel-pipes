<?php

namespace Mshule\LaravelPipes;

use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Contracts\Foundation\Application;
use Mshule\LaravelPipes\Exceptions\NotFoundPipeException;

class PipeRequestHandler
{
    /**
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The piper instance.
     *
     * @var \Mshule\LaravelPipes\Piper
     */
    protected $piper;

    /**
     * The application's pipe middleware.
     *
     * @var array
     */
    protected $piperMiddleware = [];

    /**
     * Create a new PipeRequestHandler instance.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param \Mshule\LaravelPipes\Piper                   $piper
     */
    public function __construct(Application $app, Piper $piper)
    {
        $this->app = $app;
        $this->piper = $piper;

        foreach ($this->piperMiddleware as $key => $middleware) {
            $piper->aliasMiddleware($key, $middleware);
        }
    }

    /**
     * Handle an incoming HTTP request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request)
    {
        // load pipes.php file
        // check if pipe was added
        try {
            $response = $this->sendRequestThroughPipes($request);
        } catch (NotFoundPipeException $e) {
            // throw exception if no pipe was found & no fallback was added
            throw new NotFoundPipeException($request);
        }

        return $response;
    }

    /**
     * Send the given request through the middleware / pipes.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    protected function sendRequestThroughPipes($request)
    {
        return (new Pipeline($this->app))
                    ->send($request)
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
}
