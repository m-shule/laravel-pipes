<?php

namespace Mshule\LaravelPipes;

use Illuminate\Support\Str;
use Illuminate\Routing\RouteParameterBinder;

class PipeParameterBinder extends RouteParameterBinder
{
    /**
     * Get the parameter matches for the path portion of the URI.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function bindPathParameters($request)
    {
        $replacements = collect($this->route->parameterNames())->map(function ($param) use ($request) {
            return $request->{$param};
        })->toArray();

        $path = preg_replace_array('/\\{[a-zA-Z]+\\}/', $replacements, $this->route->uri());
        $path = Str::startsWith($path, '/') ? $path : '/' . $path;

        preg_match($this->route->compiled->getRegex(), $path, $matches);

        return $this->matchToKeys(array_slice($matches, 1));
    }
}
