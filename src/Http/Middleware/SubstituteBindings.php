<?php

namespace Mshule\LaravelPipes\Http\Middleware;

use Closure;
use Mshule\LaravelPipes\Facades\Pipe;

class SubstituteBindings
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        Pipe::substituteBindings($pipe = $request->pipe());

        Pipe::substituteImplicitBindings($pipe);

        return $next($request);
    }
}
