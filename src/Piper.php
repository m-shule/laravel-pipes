<?php

namespace Mshule\LaravelPipes;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Container\Container;
use Mshule\LaravelPipes\Contracts\Registrar as RegistrarContract;

class Piper extends Router implements RegistrarContract
{
    /**
     * The pipe collection instance.
     *
     * @var \Mshule\LaravelPipes\PipeCollection
     */
    protected $pipes;

    /**
     * The response resolver callback.
     *
     * @var \Closure
     */
    protected $responseResolver;

    /**
     * Create a new Piper instance.
     */
    public function __construct(Container $container = null)
    {
        $this->pipes = new PipeCollection();
        $this->container = $container ?: new Container();
    }

    /**
     * Register a new pipe with the given verbs.
     *
     * @param string                         $inputs
     * @param string                         $cue
     * @param \Closure|array|string|callable $action
     *
     * @return \Mshule\LaravelPipes\Pipe
     */
    public function any($cue, $action = [])
    {
        return $this->addPipe(resolve('pipe_any'), $cue, $action);
    }

    /**
     * Register a new Fallback pipe with the piper.
     *
     * @param \Closure|array|string|callable|null $action
     *
     * @return \Mshule\LaravelPipes\Pipe
     */
    public function fallback($action)
    {
        $placeholder = 'fallbackPlaceholder';

        return $this->addPipe(
            resolve('pipe_any'),
            "{{$placeholder}}",
            $action
        )->where($placeholder, '.*')->fallback();
    }

    /**
     * Register a new pipe with the given verbs.
     *
     * @param string                         $key
     * @param string                         $cue
     * @param \Closure|array|string|callable $action
     *
     * @return \Mshule\LaravelPipes\Pipe
     */
    public function match($key, $cue, $action = [])
    {
        // If only two arguments were entered and the first
        // does not contain a colon (:), we assume the
        // user either wants to allow any input or
        // will specify a specific input later on
        if (2 === count(func_get_args()) && (is_string($key) && ! Str::contains($key, ':'))) {
            return $this->addPipe('placeholder', $key, $cue);
        }

        return $this->addPipe($key, $cue, $action);
    }

    /**
     * Merge the given array with the last group stack.
     *
     * @param array $new
     *
     * @return array
     */
    public function mergeWithLastGroup($new)
    {
        return PipeGroup::merge($new, end($this->groupStack));
    }

    /**
     * Add a pipe to the underlying pipe collection.
     *
     * @param string                         $inputs
     * @param string                         $cue
     * @param \Closure|array|string|callable $action
     *
     * @return \Mshule\LaravelPipes\Pipe
     */
    public function addPipe($key, $cue, $action = [])
    {
        return $this->pipes->add($this->createPipe($key, $cue, $action));
    }

    /**
     * Add a route to the underlying route collection.
     *
     * @param array|string                        $methods
     * @param string                              $uri
     * @param \Closure|array|string|callable|null $action
     */
    public function addRoute($methods, $uri, $action)
    {
        $this->handleNotIntendedMethods();
    }

    /**
     * Create a new pipe instance.
     *
     * @param string $key
     * @param string $cue
     * @param mixed  $action
     *
     * @return \Mshule\LaravelPipes\Pipe
     */
    protected function createPipe($key, $cue, $action = [])
    {
        // if the input was passed in combination with the cue
        // seperated by a colon (:), the values need to
        // be reassigned to the right variable.
        if (is_string($key) && Str::contains($key, ':')) {
            list($key, $cue, $action) = array_merge(
                explode(':', $key),
                [array_merge(['uses' => $cue], $action)]
            );
        }

        // if the input was passed through the fluent api the
        // order of the func argument have to be rearranged.
        if (($cue instanceof Closure && is_callable($cue)) || Str::contains($cue, '@')) {
            list($key, $cue, $action) = [$action, $key, $cue];
        }

        // If the pipe is pointing to a controller we will parse the pipe action into
        // an acceptable array format before registering it and creating this pipe
        // instance itself. We need to build the Closure that will call this out.
        if ($this->actionReferencesController($action)) {
            $action = $this->convertToControllerAction($action);
        }

        $pipe = $this->newPipe(
            $cue,
            $action,
            $key
        );

        // If we have groups that need to be merged, we will merge them now after this
        // pipe has already been created and is ready to go. After we're done with
        // the merge we will be ready to return the pipe back out to the caller.
        if ($this->hasGroupStack()) {
            $this->mergeGroupAttributesIntoRoute($pipe);
        }

        $this->addWhereClausesToRoute($pipe);

        return $pipe;
    }

    /**
     * Create a new Pipe object.
     *
     * @param string       $cue
     * @param mixed        $action
     * @param array|string $key
     *
     * @return \Mshule\LaravelPipes\Pipe
     */
    protected function newPipe($cue, $action, $key = '')
    {
        return (new Pipe($cue, $action, $key))
                    ->setPiper($this)
                    ->setContainer($this->container);
    }

    /**
     * Dispatch the request to a pipe and return the response.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function dispatchToRoute(Request $request)
    {
        return $this->runPipe($request, $this->findPipe($request));
    }

    /**
     * Find the pipe matching a given request.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Mshule\LaravelPipes\Pipe $pipe
     */
    protected function findPipe($request)
    {
        $this->current = $pipe = $this->pipes->match($request);

        return $pipe;
    }

    /**
     * Return the response for the given pipe.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Mshule\LaravelPipes\Pipe $pipe
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    protected function runPipe(Request $request, Pipe $pipe)
    {
        $request->setPipeResolver(function () use ($pipe) {
            return $pipe;
        });

        return $this->prepareResponse(
            $request,
            $this->runRouteWithinStack($pipe, $request)
        );
    }

    /**
     * Get the response resolver callback.
     *
     * @return \Closure
     */
    public function getResponseResolver()
    {
        return $this->responseResolver ?: function ($request) {
            return response('ok');
        };
    }

    /**
     * Set the response resolver callback.
     *
     * @param \Closure $callback
     *
     * @return $this
     */
    public function setResponseResolver(Closure $callback)
    {
        $this->responseResolver = $callback;

        return $this;
    }

    /**
     * Return standard response.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function response($request)
    {
        return call_user_func($this->getResponseResolver(), $request);
    }

    /**
     * Throws exceptions to notify user of methods not allowed to use in a pipe context.
     *
     * @throws Exception
     */
    private function handleNotIntendedMethods()
    {
        throw new Exception('The methods solely used by the router instance are not intended to be used in a pipe context!');
    }

    /**
     * Dynamically handle calls into the piper instance.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if ('middleware' === $method) {
            return (new PipeRegistrar($this))->attribute($method, is_array($parameters[0]) ? $parameters[0] : $parameters);
        }

        return (new PipeRegistrar($this))->attribute($method, $parameters[0]);
    }
}
