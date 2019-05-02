<?php

namespace Mshule\LaravelPipes\Http\Middleware;

use Closure;
use Mshule\LaravelPipes\Contracts\Registrar;

class SubstituteBindings
{
    /**
     * The router instance.
     *
     * @var \Mshule\LaravelPipes\Contracts\Registrar
     */
    protected $piper;

    /**
     * Create a new bindings substitutor.
     *
     * @param  \Mshule\LaravelPipes\Contracts\Registrar  $piper
     * @return void
     */
    public function __construct(Registrar $piper)
    {
        $this->piper = $piper;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->piper->substituteBindings($pipe = $request->pipe());

        $this->piper->substituteImplicitBindings($pipe);

        return $next($request);
    }
}
