<?php

namespace Mshule\LaravelPipes\Contracts;

use Mshule\LaravelPipes\Pipe;

interface ControllerDispatcher
{
    /**
     * Dispatch a request to a given controller and method.
     *
     * @param \Mshule\LaravelPipes\Pipe $pipe
     * @param mixed                     $controller
     * @param string                    $method
     *
     * @return mixed
     */
    public function dispatch(Pipe $pipe, $controller, $method);

    /**
     * Get the middleware for the controller instance.
     *
     * @param \Illuminate\Routing\Controller $controller
     * @param string                         $method
     *
     * @return array
     */
    public function getMiddleware($controller, $method);
}
