<?php

namespace Mshule\LaravelPipes;

use Countable;
use ArrayIterator;
use IteratorAggregate;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Mshule\LaravelPipes\Exceptions\NotFoundPipeException;

class PipeCollection implements Countable, IteratorAggregate
{
    /**
     * An array of the pipes keyed by method.
     *
     * @var array
     */
    protected $pipes = [];

    /**
     * A flattened array of all of the pipes.
     *
     * @var array
     */
    protected $allPipes = [];

    /**
     * Add a Route instance to the collection.
     *
     * @param \Mshule\LaravelPipes\Pipe $pipe
     *
     * @return \Mshule\LaravelPipes\Pipe
     */
    public function add(Pipe $pipe)
    {
        $this->addToCollections($pipe);

        return $pipe;
    }

    /**
     * Add the given pipe to the arrays of pipes.
     *
     * @param \Mshule\LaravelPipes\Pipe $pipe
     */
    protected function addToCollections($pipe)
    {
        $cue = $pipe->cue();
        $key = $pipe->key();

        $this->pipes[$key][$cue] = $pipe;
        $this->allPipes[$key . $cue] = $pipe;
    }

    /**
     * Find the first pipe matching a given request.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Mshule\LaravelPipes\Pipe $pipe
     *
     * @throws Mshule\LaravelPipes\Exceptions\NotFoundPipeException
     */
    public function match(Request $request)
    {
        $pipes = $this->get($request->keys());

        // First, we will see if we can find a matching pipe for this current request
        // method. If we can, great, we can just return it so that it can be called
        // by the consumer.
        $pipe = $this->matchAgainstPipes($pipes, $request);

        if (! is_null($pipe)) {
            return $pipe->bind($request);
        }

        throw new NotFoundPipeException($request);
    }

    /**
     * Get pipes from the collection by attribute.
     *
     * @param string|null $
     *
     * @return array
     */
    public function get($keys = [])
    {
        if (0 === count($keys)) {
            return $this->getPipes();
        }

        array_push($keys, resolve('pipe_any'));

        return collect($keys)->map(function ($key) {
            return Arr::get($this->pipes, $key, []);
        })
            ->flatten()
            ->toArray();
    }

    /**
     * Determine if a pipe in the array matches the request.
     *
     * @param array                    $pipes
     * @param \Illuminate\Http\Request $request
     *
     * @return \Mshule\LaravelPipes\Pipe $pipe|null
     */
    protected function matchAgainstPipes(array $pipes, $request)
    {
        [$fallbacks, $pipes] = collect($pipes)->partition(function ($route) {
            return $route->isFallback;
        });

        return $pipes->merge($fallbacks)->first(function ($value) use ($request) {
            return $value->matches($request);
        });
    }

    /**
     * Get all of the pipes in the collection.
     *
     * @return array
     */
    public function getPipes()
    {
        return array_values($this->allPipes);
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->getPipes());
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->getPipes());
    }
}
