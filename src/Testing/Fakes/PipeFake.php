<?php

namespace Mshule\LaravelPipes\Testing\Fakes;

use Mshule\LaravelPipes\Piper;
use Illuminate\Support\Facades\Event;
use Mshule\LaravelPipes\Events\IncomingPipeRequest;
use Mshule\LaravelPipes\Events\IncomingPipeResponse;

class PipeFake extends Piper
{
    /**
     * Assert if a request was dispatched based on a truth-test callback.
     *
     * @param \Closure $callback
     * @return void
     */
    public function assertRequested($callback = null)
    {
        $truthTestCallback = $this->getTruthTestCallback($callback, 'request');

        Event::assertDispatched(IncomingPipeRequest::class, $truthTestCallback);
    }

    /**
     * Assert if a response was dispatched based on a truth-test callback.
     *
     * @param \Closure $callback
     * @return void
     */
    public function assertResponded($callback = null)
    {
        $truthTestCallback = $this->getTruthTestCallback($callback, 'response');

        Event::assertDispatched(IncomingPipeResponse::class, $truthTestCallback);
    }

    /**
     * Get truth test callback.
     *
     * @param \Closure|null $callback
     * @param string|null $property
     * @return \Closure
     */
    protected function getTruthTestCallback($callback = null, $property = null)
    {
        return is_callable($callback)
            ? function ($event) use ($callback, $property) {
                $callback($event->{$property});

                return true;
            }
        : null;
    }

    public function afterResponse($listener)
    {
        $this->listen(IncomingPipeResponse::class, $listener);
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * @param  string|array  $events
     * @param  mixed  $listener
     * @return void
     */
    public function listen($events, $listener)
    {
        Event::listen($events, $listener);
    }
}
