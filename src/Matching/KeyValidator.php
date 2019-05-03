<?php

namespace Mshule\LaravelPipes\Matching;

use Illuminate\Http\Request;
use Mshule\LaravelPipes\Pipe;

class KeyValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a route and request.
     *
     * @param \Mshule\LaravelPipes\Pipe $pipe
     * @param \Illuminate\Http\Request  $request
     *
     * @return bool
     */
    public function matches(Pipe $pipe, Request $request)
    {
        $keys = $request->keys();

        array_push($keys, resolve('pipe_any'));

        return in_array($pipe->key(), $keys);
    }
}
