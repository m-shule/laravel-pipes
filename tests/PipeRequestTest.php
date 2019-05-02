<?php

namespace Mshule\LaravelPipes\Tests;

use Mshule\LaravelPipes\Facades\Pipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mshule\LaravelPipes\Testing\MakesPipeRequests;
use Mshule\LaravelPipes\Tests\Fixtures\Models\Todo;
use Mshule\LaravelPipes\Exceptions\NotFoundPipeException;

class PipeRequestTest extends TestCase
{
    use RefreshDatabase, MakesPipeRequests;

    /** @test */
    public function a_not_found_pipe_exception_is_thrown_if_no_controller_was_found()
    {
        $this->withoutExceptionHandling();
        $this->expectException(NotFoundPipeException::class);

        $this->pipe();
    }

    /** @test */
    public function it_renders_a_not_found_pipe_exception_as_404_not_found_http_response()
    {
        $this->pipe()
            ->assertNotFound();
    }

    /** @test */
    public function it_can_resolve_pipes_to_callbacks()
    {
        Pipe::match('text', 'test', function () {
            return response('pipe was resolved', 200);
        });

        $this->pipe([
                'text' => 'test',
            ])
            ->assertOk()
            ->assertSee('pipe was resolved');
    }

    /** @test */
    public function it_can_resolve_pipes_to_controller_actions()
    {
        Pipe::match('text', 'something', '\Mshule\LaravelPipes\Tests\Fixtures\Controllers\TestController@doSomething');

        $this->pipe([
                'text' => 'something',
            ])
            ->assertOk()
            ->assertSee('did something');
    }

    /** @test */
    public function it_can_resolve_pipes_to_controller_actions_through_using_the_fluent_api()
    {
        Pipe::match('text:something', '\Mshule\LaravelPipes\Tests\Fixtures\Controllers\TestController@doSomething');

        $this->pipe([
                'text' => 'something',
            ])
            ->assertOk()
            ->assertSee('did something');
    }

    /** @test */
    public function it_can_resolve_pipes_with_middlewares()
    {
        Pipe::middleware(function ($request, $next) {
            return 'middleware succeeded';
        })->match('text', 'middle', function () {
            return 'middleware failed';
        });

        $this->pipe([
                'text' => 'middle',
            ])
            ->assertOk()
            ->assertSee('middleware succeeded');
    }

    /** @test */
    public function it_can_resolve_grouped_pipes_and_pass_all_attributes_to_the_contained_pipes()
    {
        Pipe::attributes('text')->group(function () {
            Pipe::match('something', function () {//'TestController@doSomething');
                return 'did something';
            });
        });

        $this->pipe([
                'text' => 'something',
            ])
            ->assertOk()
            ->assertSee('did something');

        Pipe::group(
            [
                'namespace' => '\Mshule\LaravelPipes\Tests\Fixtures\Controllers',
                'input' => 'other',
            ],
            function () {
                Pipe::match('something', 'TestController@doSomething');
            }
        );

        $this->pipe([
                'other' => 'something',
            ])
            ->assertOk()
            ->assertSee('did something');
    }

    /** @test */
    public function it_can_add_fallback_pipes_to_handle_any_request_which_could_not_be_matched_otherwise()
    {
        Pipe::fallback(function () {
            return 'no other pipe did match up';
        });

        $this->pipe(['foo' => 'bar'])
            ->assertOk()
            ->assertSee('no other pipe did match up');
    }

    /** @test */
    public function it_can_match_dynamic_parameters()
    {
        Pipe::match('trigger:name {text}', function ($text) {
            return "you said {$text}";
        });

        $this->pipe(['trigger' => 'name', 'text' => 'something'])
            ->assertOk()
            ->assertSee('you said something');
    }

    /** @test */
    public function it_can_match_multiple_dynamic_parameters()
    {
        Pipe::any('{name} {other}', function ($name, $other) {
            return "$name $other";
        });

        $this->pipe(['name' => 'foo', 'other' => 'bar'])
            ->assertOk()
            ->assertSee('foo bar');
    }

    /** @test */
    public function it_can_match_dynamic_parameters_to_any_request()
    {
        Pipe::any('name {text}', function ($text) {
            return "you said {$text}";
        });

        $this->pipe(['bla' => 'name', 'text' => 'something'])
            ->assertOk()
            ->assertSee('you said something');
    }

    /** @test */
    public function it_can_add_conditions_to_pipe_definitions()
    {
        Pipe::any('{name}', function ($name) {
            return $name;
        })->where('name', 'foo');

        $this->pipe(['name' => 'foo'])
            ->assertOk()
            ->assertSee('foo');
        $this->pipe(['name' => 'bar'])
            ->assertNotFound();
    }

    /** @test */
    public function it_can_bind_models_to_pipe_requests()
    {
        $todo = Todo::create(['name' => 'Foo Bar']);

        Pipe::middleware('pipe')->any('{todo}', function (Todo $todo) {
            return "You fetched {$todo->name} Todo";
        });

        $this->pipe(['todo' => $todo->id])
            ->assertOk()
            ->assertSee('You fetched Foo Bar Todo');
    }

    /** @test */
    public function it_can_load_pipes_from_files()
    {
        Pipe::namespace('Test')->group(__DIR__ . '/Fixtures/pipes.php');

        $this->pipe(['test' => 'ping'])
            ->assertOk()
            ->assertSee('pong');
    }
}
