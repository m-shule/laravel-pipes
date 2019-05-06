<?php

namespace Mshule\LaravelPipes\Jobs;

use Illuminate\Bus\Queueable;
use Mshule\LaravelPipes\Kernel;
use Mshule\LaravelPipes\Request;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Mshule\LaravelPipes\Events\IncomingPipeRequest;
use Mshule\LaravelPipes\Events\IncomingPipeResponse;

class ExecutePipeRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Incoming Request data.
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct(...$data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $kernel = resolve(Kernel::class);
        $request = new Request(...$this->data);

        event(new IncomingPipeRequest($request));

        $response = $kernel->handle($request);

        event(new IncomingPipeResponse($response));

        $kernel->terminate($request, $response);
    }
}
