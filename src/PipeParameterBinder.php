<?php

namespace Mshule\LaravelPipes;

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
        preg_match($this->route->compiled->getRegex(), $this->route->path($request), $matches);

        return $this->matchToKeys(array_slice($matches, 1));
    }
}
