<?php

namespace Mshule\LaravelPipes;

use Countable;
use ArrayIterator;
use IteratorAggregate;
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

        foreach ($pipe->inputs() as $attribute) {
            $this->pipes[$attribute][$cue] = $pipe;
        }

        $this->allPipes[$attribute . $cue] = $pipe;
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
        $attributeKeys = array_keys($request->all());

        if (count($attributeKeys) === 0) {
            throw new NotFoundPipeException(request());
        }

        $pipes = $this->get($attributeKeys);

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
     * Get pipes from the collection by attribute.
     *
     * @param string|null $
     *
     * @return array
     */
    public function get($keys = [])
    {
        return collect($this->pipes)
            ->filter(function ($value, $key) use ($keys) {
                return '*' === $key || in_array($key, $keys);
            })
            ->flatten()
            ->toArray();
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
