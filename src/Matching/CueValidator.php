<?php

namespace Mshule\LaravelPipes\Matching;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Mshule\LaravelPipes\Pipe;

class CueValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a pipe and request.
     *
     * @param \Mshule\LaravelPipes\Pipe $pipe
     * @param \Illuminate\Http\Request  $request
     *
     * @return bool
     */
    public function matches(Pipe $pipe, Request $request)
    {
        // if cue has no static params we have to let the request
        // pass and trust in the PatternValidator to detect
        // mistakes with the parameters.
        if (Str::startsWith($pipe->cue(), '{')) {
            return true;
        }

        $values = array_values($request->input());

        array_push($values, resolve('pipe_any'));

        $matched = collect($values)->contains(function ($value) use ($pipe) {
            return Str::startsWith($pipe->cue(), $value);
        });

        if ($matched || ! $pipe->hasAlias()) {
            return $matched;
        }

        // if a pipe has alias specified we will check whether
        // the alias and request values have a common subset
        return count(array_intersect($values, $pipe->getAlias())) > 0;
    }
}
