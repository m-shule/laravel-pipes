<?php

namespace Mshule\LaravelPipes\Tests\Fixtures\Controllers;

use Illuminate\Routing\Controller;

class TestController extends Controller
{
    public function doSomething()
    {
        return response('did something', 200);
    }
}
