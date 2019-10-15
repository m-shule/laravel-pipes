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
        if ($pipe->cueStartsWithPlaceholder()) {
            return true;
        }

        $any = resolve('pipe_any');
        $keys = $pipe->key() === $any ? $request->all() : $request->only($pipe->key());
        $values = array_map('strtolower', array_values($keys));

        array_push($values, $any);

        $matched = collect($values)->contains(function ($value) use ($pipe) {
            if (! $pipe->cueContainsPlaceholder()) {
                return Str::startsWith($value, $pipe->cue());
            }

            if (! $pipe->cueStartsWithPlaceholder()) {
                return Str::startsWith($value, trim(Str::before($pipe->cue(), '{')));
            }

            // to be able to match starting strings with $cue and including a
            // param `trigger {param}` inside the cue we will figure out
            // which string is longer and use this for our truth test.
            [$haystack, $needle] = strlen($value) >= strlen($pipe->cue())
                ? [$value, $pipe->cue()]
                : [$pipe->cue(), $value];

            return Str::startsWith($haystack, $needle);
        });

        if ($matched || ! $pipe->hasAlias()) {
            return $matched;
        }

        // if a pipe has alias specified we will check whether
        // the alias and request values have a common subset
        return collect($values)->contains(function ($value) use ($pipe) {
            return $pipe->hasAlias($value);
        });
    }
}
