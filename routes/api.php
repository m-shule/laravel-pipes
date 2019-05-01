<?php

use Illuminate\Http\Request;
use Mshule\LaravelPipes\PipeRequestHandler;

Route::post(config('pipes.incoming_request_path'), function (Request $request) {
    $pipeResponse = resolve(PipeRequestHandler::class)->handle($request);

    return $pipeResponse;
});
