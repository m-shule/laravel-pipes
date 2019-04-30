<?php

namespace Mshule\LaravelPipes;

use ReflectionFunction;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Container\Container;
use Illuminate\Routing\RouteDependencyResolverTrait;
use Mshule\LaravelPipes\Contracts\ControllerDispatcher as ControllerDispatcherContract;

class Pipe
{
    use RouteDependencyResolverTrait;

    /**
     * The cue pattern the pipe responds to.
     *
     * @var string
     */
    public $cue;

    /**
     * The attributes the pipe respond to.
     *
     * @var array
     */
    public $attributes;

    /**
     * The route action array.
     *
     * @var array
     */
    public $action;

    /**
     * The controller instance.
     *
     * @var mixed
     */
    public $controller;

    /**
     * The computed gathered middleware.
     *
     * @var array|null
     */
    public $computedMiddleware;

    /**
     * The piper instance used by the pipe.
     *
     * @var \Mshule\LaravelPipes\Piper
     */
    protected $piper;

    /**
     * The container instance used by the pipe.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Create a new Pipe instance.
     *
     * @param array|string   $attributes
     * @param string         $cue
     * @param \Closure|array $action
     */
    public function __construct($attributes, $cue, $action)
    {
        $this->cue = $cue;
        $this->attributes = (array) $attributes;
        $this->action = $this->parseAction($action);
    }

    /**
     * Parse the route action into a standard array.
     *
     * @param callable|array|null $action
     *
     * @throws \UnexpectedValueException
     *
     * @return array
     */
    protected function parseAction($action)
    {
        return PipeAction::parse($this->cue, $action);
    }

    /**
     * Run the pipe action and return the response.
     *
     * @return mixed
     */
    public function run()
    {
        $this->container = $this->container ?: new Container();

        try {
            if ($this->isControllerAction()) {
                return $this->runController();
            }

            return $this->runCallable();
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Set the piper instance on the pipe.
     *
     * @param \Mshule\LaravelPipes\Piper $piper
     *
     * @return $this
     */
    public function setPiper(Piper $piper)
    {
        $this->piper = $piper;

        return $this;
    }

    /**
     * Set the container instance on the route.
     *
     * @param \Illuminate\Container\Container $container
     *
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get the cue associated with the pipe.
     *
     * @return string
     */
    public function cue()
    {
        return $this->cue;
    }

    /**
     * Get the attributes the pipe responds to.
     *
     * @return array
     */
    public function attributes()
    {
        return $this->attributes;
    }

    /**
     * Get the action array or one of its properties for the pipe.
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public function getAction($key = null)
    {
        return Arr::get($this->action, $key);
    }

    /**
     * Set the action array for the pipe.
     *
     * @param array $action
     *
     * @return $this
     */
    public function setAction(array $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get all middleware, including the ones from the controller.
     *
     * @return array
     */
    public function gatherMiddleware()
    {
        if (! is_null($this->computedMiddleware)) {
            return $this->computedMiddleware;
        }

        $this->computedMiddleware = [];

        return $this->computedMiddleware = $this->middleware();
    }

    /**
     * Get or set the middlewares attached to the pipe.
     *
     * @param array|string|null $middleware
     *
     * @return $this|array
     */
    public function middleware($middleware = null)
    {
        if (is_null($middleware)) {
            return (array) ($this->action['middleware'] ?? []);
        }

        if (is_string($middleware)) {
            $middleware = func_get_args();
        }

        $this->action['middleware'] = array_merge(
            (array) ($this->action['middleware'] ?? []),
            $middleware
        );

        return $this;
    }

    /**
     * Get the dispatcher for the pipe's controller.
     *
     * @return \Illuminate\Routing\Contracts\ControllerDispatcher
     */
    public function controllerDispatcher()
    {
        if ($this->container->bound(ControllerDispatcherContract::class)) {
            return $this->container->make(ControllerDispatcherContract::class);
        }

        return new ControllerDispatcher($this->container);
    }

    /**
     * Checks whether the pipe's action is a controller.
     *
     * @return bool
     */
    protected function isControllerAction()
    {
        return is_string($this->action['uses']);
    }

    /**
     * Run the pipe action and return the response.
     *
     * @return mixed
     */
    protected function runCallable()
    {
        $callable = $this->action['uses'];

        return $callable(...array_values($this->resolveMethodDependencies(
            [],
            new ReflectionFunction($this->action['uses'])
        )));
    }

    /**
     * Run the pipe action and return the response.
     *
     * @throws \Mshule\LaravelPipes\Exceptions\NotFoundPipeException
     *
     * @return mixed
     */
    protected function runController()
    {
        return $this->controllerDispatcher()->dispatch(
            $this,
            $this->getController(),
            $this->getControllerMethod()
        );
    }

    /**
     * Get the controller instance for the route.
     *
     * @return mixed
     */
    public function getController()
    {
        if (! $this->controller) {
            $class = $this->parseControllerCallback()[0];

            $this->controller = $this->container->make(ltrim($class, '\\'));
        }

        return $this->controller;
    }

    /**
     * Get the controller method used for the route.
     *
     * @return string
     */
    protected function getControllerMethod()
    {
        return $this->parseControllerCallback()[1];
    }

    /**
     * Parse the controller.
     *
     * @return array
     */
    protected function parseControllerCallback()
    {
        return Str::parseCallback($this->action['uses']);
    }

    /**
     * Determine if the pipe matches given request.
     *
     * @param \Illuminate\Http\Request $request
     * @param bool                     $includingMethod
     *
     * @return bool
     */
    public function matches(Request $request)
    {
        return collect($request->all())->filter(function ($value, $key) {
            return in_array($key, $this->attributes);
        })->contains(function ($cue) {
            return $cue === $this->cue;
        });
    }
}
