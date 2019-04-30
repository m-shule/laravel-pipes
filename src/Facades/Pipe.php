<?php

namespace Mshule\LaravelPipes\Facades;

use Illuminate\Support\Facades\Facade;

class Pipe extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'piper';
    }
}
