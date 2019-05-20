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
     * Destruct a request to use queable instances.
     *
     * @param HttpRequest $request
     *
     * @return array
     */
    public static function destruct(HttpRequest $request)
    {
        return [
            $request->query(),
            $request->post(),
            $request->input(),
            $request->cookie(),
            $request->file(),
            $request->server(),
            $request->getContent(),
        ];
    }

    /**
     * Reconstruct request from array.
     *
     * @param array $data
     *
     * @return self
     */
    public static function reconstruct($data)
    {
        return new self(...$data);
    }

    /**
     * Get the pipe resolver callback.
     *
     * @return \Closure
     */
    public function getPipeResolver()
    {
        return $this->pipeResolver ?: function () {
        };
    }

    /**
     * Set the Pipe resolver callback.
     *
     * @param \Closure $callback
     *
     * @return $this
     */
    public function setPipeResolver(Closure $callback)
    {
        $this->pipeResolver = $callback;

        return $this;
    }

    /**
     * Get the pipe handling the request.
     *
     * @param string|null $param
     * @param mixed       $default
     *
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
