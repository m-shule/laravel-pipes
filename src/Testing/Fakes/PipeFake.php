<?php

namespace Mshule\LaravelPipes\Testing\Fakes;

use Illuminate\Support\Facades\Event;
use Mshule\LaravelPipes\Events\IncomingPipeRequest;
use Mshule\LaravelPipes\Events\IncomingPipeResponse;
use Mshule\LaravelPipes\Piper;

class PipeFake extends Piper
{
    /**
     * Assert if a request was dispatched based on a truth-test callback.
     *
     * @param \Closure $callback
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
     * @param string|null   $property
     *
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
}
