<?php

namespace Mshule\LaravelPipes\Events;

use Mshule\LaravelPipes\Request;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

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
