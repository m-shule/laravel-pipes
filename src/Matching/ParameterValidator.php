<?php

namespace Mshule\LaravelPipes\Matching;

use Illuminate\Http\Request;
use Mshule\LaravelPipes\Pipe;

class ParameterValidator implements ValidatorInterface
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
        if (count($pipe->parameterNames()) === 0) {
            return true;
        }

        foreach ($pipe->parameterNames() as $name) {
            if (! in_array($name, array_keys($request->all()))) {
                return false;
            }
        }

        return true;
    }
}
