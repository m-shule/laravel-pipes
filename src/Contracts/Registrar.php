<?php

namespace Mshule\LaravelPipes\Contracts;

interface Registrar
{
    /**
     * Register a new pipe with the given inputs.
     *
     * @param string                         $attributes
     * @param string                         $cue
     * @param \Closure|array|string|callable $action
     *
     * @return \Mshule\LaravelPipes\Pipe
     */
    public function match($attributes, $cue, $action);

    /**
     * Register a new pipe with any inputs.
     *
     * @param string                         $cue
     * @param \Closure|array|string|callable $action
     * @return \Mshule\LaravelPipes\Pipe
     */
    public function any($cue, $action);

    /**
     * Create a pipe group with shared attributes.
     *
     * @param array           $attributes
     * @param \Closure|string $pipes
     */
    public function group(array $attributes, $pipes);

    /**
     * Substitute the pipe bindings onto the pipe.
     *
     * @return \Mshule\LaravelPipes\Pipe  $pipe
     * @return \Mshule\LaravelPipes\Pipe
     */
    public function substituteBindings($pipe);

    /**
     * Substitute the implicit Eloquent model bindings for the pipe.
     *
     * @param \Mshule\LaravelPipes\Pipe  $pipe
     * @return void
     */
    public function substituteImplicitBindings($pipe);
}
