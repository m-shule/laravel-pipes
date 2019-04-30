<?php

namespace Mshule\LaravelPipes;

use Closure;
use BadMethodCallException;
use InvalidArgumentException;

class PipeRegistrar
{
    /**
     * The piper instance.
     *
     * @var \Mshule\LaravelPipes\Piper
     */
    protected $piper;

    /**
     * The methods to dynamically pass through to the router.
     *
     * @var array
     */
    protected $passthru = [
        'match',
    ];

    /**
     * The attributes that can be set through this class.
     *
     * @var array
     */
    protected $allowedAttributes = [
        'middleware', 'namespace',
    ];

    /**
     * Create a new pipe registrar instance.
     *
     * @param Piper $piper
     */
    public function __construct(Piper $piper)
    {
        $this->piper = $piper;
    }

    /**
     * Set the value for a given attribute.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function attribute($key, $value)
    {
        if (! in_array($key, $this->allowedAttributes)) {
            throw new InvalidArgumentException("Attribute [{$key}] does not exist.");
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Create a route group with shared attributes.
     *
     * @param \Closure|string $callback
     */
    public function group($callback)
    {
        $this->piper->group($this->attributes, $callback);
    }

    /**
     * Register a new pipe with the piper.
     *
     * @param string                     $method
     * @param string                     $uri
     * @param \Closure|array|string|null $action
     *
     * @return \Mshule\LaravelPipes\Pipe
     */
    protected function registerPipe($method, $attributes, $cue, $action = null)
    {
        if (! is_array($action)) {
            $action = array_merge($this->attributes, $action ? ['uses' => $action] : []);
        }

        return $this->piper->{$method}($attributes, $cue, $this->compileAction($action));
    }

    /**
     * Compile the action into an array including the attributes.
     *
     * @param \Closure|array|string|null $action
     *
     * @return array
     */
    protected function compileAction($action)
    {
        if (is_null($action)) {
            return $this->attributes;
        }

        if (is_string($action) || $action instanceof Closure) {
            $action = ['uses' => $action];
        }

        return array_merge($this->attributes, $action);
    }

    /**
     * Dynamically handle calls into the route registrar.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @throws \BadMethodCallException
     *
     * @return \Mshule\LaravelPipes\Pipe|$this
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, $this->passthru)) {
            return $this->registerPipe($method, ...$parameters);
        }

        if (in_array($method, $this->allowedAttributes)) {
            if ('middleware' === $method) {
                return $this->attribute($method, is_array($parameters[0]) ? $parameters[0] : $parameters);
            }

            return $this->attribute($method, $parameters[0]);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.',
            static::class,
            $method
        ));
    }
}
