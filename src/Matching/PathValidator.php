<?php

namespace Mshule\LaravelPipes\Matching;

use Illuminate\Http\Request;
use Mshule\LaravelPipes\Pipe;

class PathValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a pipe and request.
     *
     * @param  \Mshule\LaravelPipes\Pipe  $pipe
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function matches(Pipe $pipe, Request $request)
    {
        if (preg_match_all("/[a-zA-Z]+\/[a-zA-Z]+/", $pipe->uri(), $matches)) {
            foreach ($matches[0] as $match) {
                list($key, $value) = explode('/', $match);
                if (! in_array($key, array_keys($request->all())) || ! in_array($value, $request->all())) {
                    return false;
                }
            }
        }

        return true;
    }
}
