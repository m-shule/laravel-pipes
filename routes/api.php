<?php

use Illuminate\Http\Request as HttpRequest;
use Mshule\LaravelPipes\Facades\Pipe;
use Mshule\LaravelPipes\Jobs\ExecutePipeRequest;
use Mshule\LaravelPipes\Request;

Route::post(config('pipes.incoming_request_path'), function (HttpRequest $request) {
    ExecutePipeRequest::dispatch(
        ...Request::destruct($request)
    )->onQueue(config('pipes.queue'));

    return Pipe::response($request);
});
