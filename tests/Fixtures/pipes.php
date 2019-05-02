<?php

use Mshule\LaravelPipes\Facades\Pipe;

Pipe::any('ping', function () {
    return 'pong';
});
