<?php

namespace Mshule\LaravelPipes\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Mshule\LaravelPipes\Request;

class IncomingPipeRequest
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var \Mshule\LaravelPipes\Request
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
