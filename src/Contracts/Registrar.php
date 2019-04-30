<?php

namespace Mshule\LaravelPipes\Contracts;

interface Registrar
{
    /**
     * Register a new pipe with the given verbs.
     *
     * @param string                         $attributes
     * @param string                         $cue
     * @param \Closure|array|string|callable $action
     *
     * @return \Mshule\LaravelPipes\Pipe
     */
    public function match($attributes, $cue, $action);

    /**
     * Create a route group with shared attributes.
     *
     * @param array           $attributes
     * @param \Closure|string $routes
     */
    public function group(array $attributes, $routes);
}
