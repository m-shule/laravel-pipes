<?php

use Mshule\LaravelPipes\Request;
use Mshule\LaravelPipes\Facades\Pipe;
use Illuminate\Http\Request as HttpRequest;
use Mshule\LaravelPipes\Jobs\ExecutePipeRequest;

Route::post(config('pipes.incoming_request_path'), function (HttpRequest $request) {
    ExecutePipeRequest::dispatch(
        ...Request::destruct($request)
    );

    return Pipe::response($request);
});
