<?php

namespace Mshule\LaravelPipes;

use Illuminate\Container\Container;
use Illuminate\Routing\RouteDependencyResolverTrait;
use Mshule\LaravelPipes\Contracts\ControllerDispatcher as ControllerDispatcherContract;

class ControllerDispatcher implements ControllerDispatcherContract
{
    use RouteDependencyResolverTrait;

    /**
     * The container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Create a new controller dispatcher instance.
     *
     * @param \Illuminate\Container\Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Dispatch a request to a given controller and method.
     *
     * @param \Mshule\LaravelPipes\Pipe $pipe
     * @param mixed                     $controller
     * @param string                    $method
     *
     * @return mixed
     */
    public function dispatch(Pipe $pipe, $controller, $method)
    {
        $parameters = $this->resolveClassMethodDependencies(
            [],
            $controller,
            $method
        );

        if (method_exists($controller, 'callAction')) {
            return $controller->callAction($method, $parameters);
        }

        return $controller->{$method}(...array_values($parameters));
    }

    /**
     * Get the middleware for the controller instance.
     *
     * @param \Illuminate\Routing\Controller $controller
     * @param string                         $method
     *
     * @return array
     */
    public function getMiddleware($controller, $method)
    {
        if (! method_exists($controller, 'getMiddleware')) {
            return [];
        }

        return collect($controller->getMiddleware())->reject(function ($data) use ($method) {
            return static::methodExcludedByOptions($method, $data['options']);
        })->pluck('middleware')->all();
    }

    /**
     * Determine if the given options exclude a particular method.
     *
     * @param string $method
     * @param array  $options
     *
     * @return bool
     */
    protected static function methodExcludedByOptions($method, array $options)
    {
        return (isset($options['only']) && ! in_array($method, (array) $options['only'])) ||
            (! empty($options['except']) && in_array($method, (array) $options['except']));
    }
}
