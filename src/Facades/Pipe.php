<?php

namespace Mshule\LaravelPipes\Facades;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;
use Mshule\LaravelPipes\Events\IncomingPipeRequest;
use Mshule\LaravelPipes\Events\IncomingPipeResponse;
use Mshule\LaravelPipes\Testing\Fakes\PipeFake;

class Pipe extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @param array|string $eventsToFake
     *
     * @return \Illuminate\Support\Testing\Fakes\EventFake
     */
    public static function fake($eventsToFake = [])
    {
        Event::fake(
            count($eventsToFake) > 0
                ? $eventsToFake
                : [IncomingPipeRequest::class, IncomingPipeResponse::class]
        );

        static::swap($fake = new PipeFake());

        return $fake;
    }

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
