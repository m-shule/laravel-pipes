<?php

use Illuminate\Http\Request;
use Mshule\LaravelPipes\Facades\Pipe;
use Mshule\LaravelPipes\Jobs\ExecutePipeRequest;

Route::post(config('pipes.incoming_request_path'), function (Request $request) {
    ExecutePipeRequest::dispatch(
        $request->query(),
        array_map('strtolower', $request->post()),
        array_map('strtolower', $request->input()),
        $request->cookie(),
        $request->file(),
        $request->server(),
        $request->getContent()
    );

    return Pipe::response($request);
});
