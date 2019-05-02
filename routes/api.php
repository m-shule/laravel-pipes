<?php

use Illuminate\Http\Request;
use Mshule\LaravelPipes\Kernel;

Route::post(config('pipes.incoming_request_path'), function (Request $request) {
    $kernel = resolve(Kernel::class);

    $response = $kernel->handle($request);

    $kernel->terminate($request, $response);

    return $response;
});
