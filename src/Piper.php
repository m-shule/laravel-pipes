<?php

namespace Mshule\LaravelPipes;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\MiddlewareNameResolver;
use Mshule\LaravelPipes\Contracts\Registrar as RegistrarContract;

class Piper implements RegistrarContract
{
    /**
     * The pipe group attribute stack.
     *
     * @var array
     */
    protected $groupStack = [];

    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The pipe collection instance.
     *
     * @var \Mshule\LaravelPipes\PipeCollection
     */
    protected $pipes;

    /**
     * The currently dispatched route instance.
     *
     * @var \Illuminate\Routing\Route|null
     */
    protected $current;

    /**
     * The request currently being dispatched.
     *
     * @var \Illuminate\Http\Request
     */
    protected $currentRequest;

    /**
     * All of the short-hand keys for middlewares.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Create a new Router instance.
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
     * @return \Mshule\LaravelPipes\Pipe
     */
    public function any($cue, $action = [])
    {
        return $this->addPipe('*', $cue, $action);
    }

    /**
     * Register a new Fallback pipe with the piper.
     *
     * @param \Closure|array|string|callable|null $action
     * @return \Mshule\LaravelPipes\Pipe
     */
    public function fallback($action)
    {
        return $this->addPipe('*:*', $action)
            ->fallback();
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
    public function match($inputs, $cue, $action = [])
    {
        // If only two arguments were entered and the first
        // does not contain a colon (:), we assume the
        // user either wants to allow any input or
        // will specify a specific input later on
        if (2 === count(func_get_args()) && (is_string($inputs) && ! Str::contains($inputs, ':'))) {
            return $this->any($inputs, $cue);
        }

        return $this->addPipe($inputs, $cue, $action);
    }

    /**
     * Create a route group with shared attributes.
     *
     * @param array           $attributes
     * @param \Closure|string $pipes
     */
    public function group(array $attributes, $pipes)
    {
        $this->updateGroupStack($attributes);

        // Once we have updated the group stack, we'll load the provided pipes and
        // merge in the group's attributes when the pipes are created. After we
        // have created the pipes, we will pop the attributes off the stack.
        $this->loadPipes($pipes);

        array_pop($this->groupStack);
    }

    /**
     * Update the group stack with the given attributes.
     *
     * @param array $attributes
     */
    protected function updateGroupStack(array $attributes)
    {
        if (! empty($this->groupStack)) {
            $attributes = $this->mergeWithLastGroup($attributes);
        }

        $this->groupStack[] = $attributes;
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
     * Load the provided pipes.
     *
     * @param \Closure|string $pipes
     */
    protected function loadPipes($pipes)
    {
        if ($pipes instanceof Closure) {
            $pipes($this);
        } else {
            throw new \Exception('Loading through files was not implemented yet');
            // (new RouteFileRegistrar($this))->register($pipes);
        }
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
    public function addPipe($inputs, $cue, $action = [])
    {
        return $this->pipes->add($this->createPipe($inputs, $cue, $action));
    }

    /**
     * Create a new pipe instance.
     *
     * @param string $inputs
     * @param string $cue
     * @param mixed  $action
     *
     * @return \Mshule\LaravelPipes\Pipe
     */
    protected function createPipe($inputs, $cue, $action = [])
    {
        // if the input was passed in combination with the cue
        // seperated by a colon (:), the values need to
        // be reassigned to the right variable.
        if (is_string($inputs) && Str::contains($inputs, ':')) {
            list($inputs, $cue, $action) = array_merge(
                explode(':', $inputs),
                [array_merge(['uses' => $cue], $action)]
            );
        }

        // if the input was passed through the fluent api the
        // order of the func argument have to be rearranged.
        if (is_callable($cue) || Str::contains($cue, '@')) {
            list($inputs, $cue, $action) = [$action, $inputs, $cue];
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
            $inputs
        );

        // If we have groups that need to be merged, we will merge them now after this
        // pipe has already been created and is ready to go. After we're done with
        // the merge we will be ready to return the pipe back out to the caller.
        if ($this->hasGroupStack()) {
            $this->mergeGroupAttributesIntoPipe($pipe);
        }

        return $pipe;
    }

    /**
     * Determine if the action is pointing to a controller.
     *
     * @param array $action
     *
     * @return bool
     */
    protected function actionReferencesController($action)
    {
        if (! $action instanceof Closure) {
            return is_string($action) || (isset($action['uses']) && is_string($action['uses']));
        }

        return false;
    }

    /**
     * Add a controller based pipe action to the action array.
     *
     * @param array|string $action
     *
     * @return array
     */
    protected function convertToControllerAction($action)
    {
        if (is_string($action)) {
            $action = ['uses' => $action];
        }

        // Here we'll merge any group "uses" statement if necessary so that the action
        // has the proper clause for this property. Then we can simply set the name
        // of the controller on the action and return the action array for usage.
        if (! empty($this->groupStack)) {
            $action['uses'] = $this->prependGroupNamespace($action['uses']);
        }

        // Here we will set this controller name on the action array just so we always
        // have a copy of it for reference if we need it. This can be used while we
        // search for a controller name or do some other type of fetch operation.
        $action['controller'] = $action['uses'];

        return $action;
    }

    /**
     * Prepend the last group namespace onto the use clause.
     *
     * @param string $class
     *
     * @return string
     */
    protected function prependGroupNamespace($class)
    {
        $group = end($this->groupStack);

        return isset($group['namespace']) && 0 !== strpos($class, '\\')
                ? $group['namespace'] . '\\' . $class : $class;
    }

    /**
     * Create a new Pipe object.
     *
     * @param string       $cue
     * @param mixed        $action
     * @param array|string $inputs
     *
     * @return \Mshule\LaravelPipes\Pipe
     */
    protected function newPipe($cue, $action, $inputs = [])
    {
        return (new Pipe($cue, $action, $inputs))
                    ->setPiper($this)
                    ->setContainer($this->container);
    }

    /**
     * Merge the group stack with the controller action.
     *
     * @param \Mshule\LaravelPipes\Pipe $pipe
     */
    protected function mergeGroupAttributesIntoPipe($pipe)
    {
        $pipe->setAction($this->mergeWithLastGroup($pipe->getAction()));
    }

    /**
     * Dispatch the request to the application.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function dispatch(Request $request)
    {
        $this->currentRequest = $request;

        return $this->dispatchToPipe($request);
    }

    /**
     * Dispatch the request to a pipe and return the response.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function dispatchToPipe(Request $request)
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

        // $this->container->instance(Pipe::class, $pipe);

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
        // $this->events->dispatch(new Events\RouteMatched($pipe, $request));

        return $this->prepareResponse(
            $request,
            $this->runPipeWithinStack($pipe, $request)
        );
    }

    /**
     * Run the given pipe within a Stack "onion" instance.
     *
     * @param \Mshule\LaravelPipes\Pipe $pipe
     * @param \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    protected function runPipeWithinStack(Pipe $pipe, Request $request)
    {
        $shouldSkipMiddleware = $this->container->bound('middleware.disable') &&
                                true === $this->container->make('middleware.disable');

        $middleware = $shouldSkipMiddleware ? [] : $this->gatherPipeMiddleware($pipe)->all();

        return (new Pipeline($this->container))
                        ->send($request)
                        ->through($middleware)
                        ->then(function ($request) use ($pipe) {
                            return $this->prepareResponse(
                                $request,
                                $pipe->run()
                            );
                        });
    }

    /**
     * Gather the middleware for the given pipe with resolved class names.
     *
     * @param \Mshule\LaravelPipes\Pipe $pipe
     *
     * @return array
     */
    public function gatherPipeMiddleware(Pipe $pipe)
    {
        return collect($pipe->gatherMiddleware())->map(function ($name) {
            return (array) MiddlewareNameResolver::resolve($name, $this->middleware, []);
        })->flatten();
    }

    /**
     * Create a response instance from the given value.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param mixed                                     $response
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function prepareResponse($request, $response)
    {
        return Route::toResponse($request, $response);
    }

    /**
     * Register a short-hand name for a middleware.
     *
     * @param string $name
     * @param string $class
     *
     * @return $this
     */
    public function aliasMiddleware($name, $class)
    {
        $this->middleware[$name] = $class;

        return $this;
    }

    /**
     * Determine if the piper currently has a group stack.
     *
     * @return bool
     */
    public function hasGroupStack()
    {
        return ! empty($this->groupStack);
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
        if ('middleware' === $method) {
            return (new PipeRegistrar($this))->attribute($method, is_array($parameters[0]) ? $parameters[0] : $parameters);
        }

        return (new PipeRegistrar($this))->attribute($method, $parameters[0]);
    }
}
