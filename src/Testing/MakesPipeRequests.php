<?php

namespace Mshule\LaravelPipes\Testing;

trait MakesPipeRequests
{
    /**
     * Makes a pipe request with the given data.
     *
     * @param array $data
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function pipe($data = [])
    {
        return $this->post(config('pipes.incoming_request_path'), $data);
    }
}
