<?php

namespace Mshule\LaravelPipes;

use Illuminate\Http\Response as HttpResponse;
use Illuminate\Foundation\Testing\TestResponse;

class Response
{
    /**
     * Creates a test response if the app is in testing environment.
     *
     * @param HttpResponse $response
     * @return \Illuminate\Http\Response|\Illuminate\Foundation\Testing\TestResponse
     */
    public static function from(HttpResponse $response)
    {
        return app()->environment('testing')
            ? new TestResponse($response)
            : $response;
    }
}
