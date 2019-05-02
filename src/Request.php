<?php

namespace Mshule\LaravelPipes;

use Closure;
use Illuminate\Http\Request as HttpRequest;

class Request extends HttpRequest
{
    /**
     * The pipe resolver callback.
     *
     * @var \Closure
     */
    protected $pipeResolver;

    /**
     * Get the pipe resolver callback.
     *
     * @return \Closure
     */
    public function getPipeResolver()
    {
        return $this->PipeResolver ?: function () {
            //
        };
    }

    /**
     * Set the Pipe resolver callback.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function setPipeResolver(Closure $callback)
    {
        $this->PipeResolver = $callback;

        return $this;
    }

    /**
     * Get the pipe handling the request.
     *
     * @param  string|null  $param
     * @param  mixed   $default
     * @return \Mshule\LaravelPipes\Pipe|object|string
     */
    public function pipe($param = null, $default = null)
    {
        $pipe = call_user_func($this->getPipeResolver());

        if (is_null($pipe) || is_null($param)) {
            return $pipe;
        }

        return $pipe->parameter($param, $default);
    }
}
