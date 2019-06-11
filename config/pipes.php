<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Load Routes File
    |--------------------------------------------------------------------------
    |
    | This value determines if a `routes/pipes.php` file should be loaded
    | on the boot method of the packages service provider. By default
    | this option is set to `false`. If you set it to true the
    | 'pipe' middleware group will be applied to all pipes
    | inside the `route/pipes.php' file.
    |
    */

    'load_routes_file' => false,

    /*
    |--------------------------------------------------------------------------
    | Namespace
    |--------------------------------------------------------------------------
    |
    | This value determines the default namespace of your pipes. By default
    | it is set to `App\Pipes\Controllers`.
    |
    */

    'namespace' => 'App\Pipes\Controllers',

    /*
    |--------------------------------------------------------------------------
    | Incoming Request Path
    |--------------------------------------------------------------------------
    |
    | This value is the name of your request path for handling incoming
    | api requests which will then be dispatched through your pipes.
    | You only need this route as endpoint for your api provider.
    |
    */

    'incoming_request_path' => 'handle-notification',

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Sets the queue on which the job is run which boots the
    | akernel and starts the whole laravel-pipe lifecycle
    |
    */

    'queue' => env('PIPE_QUEUE', 'default'),
];
