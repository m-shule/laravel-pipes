<?php

namespace Mshule\LaravelPipes\Tests\Feature;

use Mshule\LaravelPipes\Facades\Pipe;
use Mshule\LaravelPipes\Tests\TestCase;
use Mshule\LaravelPipes\Exceptions\NotFoundPipeException;

class PipeRequestHandlerTest extends TestCase
{
    protected function pipeRequest($data = [])
    {
        return $this->post(config('pipes.incoming_request_path'), $data);
    }

    /** @test */
    public function a_not_found_pipe_exception_is_thrown_if_no_controller_was_found()
    {
        $this->withoutExceptionHandling();
        $this->expectException(NotFoundPipeException::class);

        $this->pipeRequest();
    }

    /** @test */
    public function it_renders_a_not_found_pipe_exception_as_404_not_found_http_response()
    {
        $this->pipeRequest()
            ->assertNotFound();
    }

    /** @test */
    public function it_can_resolve_pipes_to_callbacks()
    {
        Pipe::match('text', 'test', function () {
            return response('pipe was resolved', 200);
        });

        $this->pipeRequest([
                'text' => 'test',
            ])
            ->assertOk()
            ->assertSee('pipe was resolved');
    }

    /** @test */
    public function it_can_resolve_pipes_to_controller_actions()
    {
        Pipe::match('text', 'something', '\Mshule\LaravelPipes\Tests\Fixtures\Controllers\TestController@doSomething');

        $this->pipeRequest([
                'text' => 'something',
            ])
            ->assertOk()
            ->assertSee('did something');
    }

    /** @test */
    public function it_can_resolve_pipes_with_middlewares()
    {
        $this->withoutExceptionHandling();
        Pipe::middleware(function ($request, $next) {
            dump('middleware');

            return $next();
        })->match('text', 'middle', function () {
            return 'middleware succeeded';
        });

        $this->pipeRequest([
                'text' => 'middle',
            ])
            ->assertOk()
            ->assertSee('middleware succeeded');
    }
}
