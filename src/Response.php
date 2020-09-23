<?php

namespace Mshule\LaravelPipes;

use Illuminate\Foundation\Application;

class Response
{
    /**
     * Creates a test response if the app is in testing environment.
     *
     * @param HttpResponse $response
     *
     * @return \Illuminate\Http\Response|\Illuminate\Foundation\Testing\TestResponse
     */
    public static function from($response)
    {
        if (!app()->environment('testing')) {
            return $response;
        }

        if (version_compare(Application::VERSION, '7.0.0', '>=')) {
            return new \Illuminate\Testing\TestResponse($response);
        }

        return new \Illuminate\Foundation\Testing\TestResponse($response);
    }
}
