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
    public function it_returns_a_ok_200er_response()
    {
        $this->pipe()
            ->assertOk()
            ->assertSee('ok');
    }

    /** @test */
    public function the_response_can_be_changed_through_the_response_resolver()
    {
        Pipe::setResponseResolver(function ($request) {
            return $request->message;
        });

        $this->pipe(['message' => 'test'])
            ->assertOk()
            ->assertSee('test');
    }

    /** @test */
    public function it_fires_an_incoming_pipe_request_event_when_a_request_is_handled_by_the_kernel()
    {
        Pipe::fake();

        $this->pipe();

        Pipe::assertRequested();
    }

    /** @test */
    public function it_fires_an_incoming_pipe_response_event_when_a_response_is_returned_by_the_kernel()
    {
        Pipe::fake();

        $this->pipe();

        Pipe::assertResponded();
    }

    /** @test */
    public function it_can_resolve_pipes_to_callbacks()
    {
        Pipe::fake();

        Pipe::match('text', 'test', function () {
            return response('pipe was resolved', 200);
        });

        $this->pipe(['text' => 'test']);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                ->assertSee('pipe was resolved');
        });
    }

    /** @test */
    public function it_matches_everything_to_lowercase()
    {
        Pipe::fake();

        Pipe::match('text', 'test', function () {
            return response('pipe was resolved', 200);
        });

        $this->pipe(['text' => 'TEST']);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                ->assertSee('pipe was resolved');
        });
    }

    /** @test */
    public function it_can_resolve_pipes_to_controller_actions()
    {
        Pipe::fake();

        Pipe::match('text', 'something', '\Mshule\LaravelPipes\Tests\Fixtures\Controllers\TestController@doSomething');

        $this->pipe([
                'text' => 'something',
            ]);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                ->assertSee('did something');
        });
    }

    /** @test */
    public function it_can_resolve_pipes_to_controller_actions_through_using_the_fluent_api()
    {
        Pipe::fake();

        Pipe::match('text:something', '\Mshule\LaravelPipes\Tests\Fixtures\Controllers\TestController@doSomething');

        $this->pipe([
                'text' => 'something',
            ]);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                ->assertSee('did something');
        });
    }

    /** @test */
    public function it_can_resolve_pipes_with_middlewares()
    {
        Pipe::fake();

        Pipe::middleware(function ($request, $next) {
            return 'middleware succeeded';
        })->match('text', 'middle', function () {
            return 'middleware failed';
        });

        $this->pipe([
                'text' => 'middle',
            ]);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                ->assertSee('middleware succeeded');
        });
    }

    /** @test */
    public function it_can_resolve_grouped_pipes_and_pass_all_key_to_the_contained_pipes()
    {
        Pipe::fake();

        Pipe::key('text')->group(function () {
            Pipe::match('something', function () {
                return 'did one';
            });
        });

        $this->pipe([
                'text' => 'something',
            ]);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                ->assertSee('did one');
        });
    }

    /** @test */
    public function it_can_add_fallback_pipes_to_handle_any_request_which_could_not_be_matched_otherwise()
    {
        Pipe::fake();

        Pipe::fallback(function () {
            return 'no other pipe did match up';
        });

        $this->pipe(['foo' => 'bar']);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                ->assertSee('no other pipe did match up');
        });
    }

    /** @test */
    public function it_can_match_dynamic_parameters()
    {
        Pipe::fake();

        Pipe::match('trigger:name {text}', function ($text) {
            return "you said {$text}";
        });

        $this->pipe(['trigger' => 'name', 'text' => 'something']);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                ->assertSee('you said something');
        });
    }

    /** @test */
    public function it_can_match_multiple_dynamic_parameters()
    {
        $this->withoutExceptionHandling();
        Pipe::fake();

        Pipe::any('{name} {other}', function ($name, $other) {
            return "$name $other";
        });

        $this->pipe(['name' => 'foo', 'other' => 'bar']);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                ->assertSee('foo bar');
        });
    }

    /** @test */
    public function it_can_match_dynamic_parameters_to_any_request()
    {
        Pipe::fake();

        Pipe::any('name {text}', function ($text) {
            return "you said {$text}";
        });

        $this->pipe(['bla' => 'name', 'text' => 'something']);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                ->assertSee('you said something');
        });
    }

    /** @test */
    public function it_can_add_conditions_to_pipe_definitions()
    {
        Pipe::fake();

        Pipe::any('{name}', function ($name) {
            return $name;
        })->where('name', 'foo');

        $this->pipe(['name' => 'foo']);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                ->assertSee('foo');
        });
    }

    /** @test */
    public function it_can_add_aliases_to_pipes()
    {
        Pipe::fake();

        Pipe::any('mshule', function () {
            return 'matched';
        })->alias(['mhule', 'mule']);

        $this->pipe(['foo' => 'mule']);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                ->assertSee('matched');
        });
    }

    /** @test */
    public function it_can_add_aliases_predefined_to_pipes()
    {
        Pipe::fake();

        Pipe::alias(['mhule', 'mule'])->any('mshule', function () {
            return 'matched';
        });

        $this->pipe(['foo' => 'mule']);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                ->assertSee('matched');
        });
    }

    /** @test */
    public function it_can_bind_models_to_pipe_requests()
    {
        Pipe::fake();

        $todo = Todo::create(['name' => 'Foo Bar']);

        Pipe::middleware('pipe')->any('{todo}', function (Todo $todo) {
            return "You fetched {$todo->name} Todo";
        });

        $this->pipe(['todo' => $todo->id]);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                ->assertSee('You fetched Foo Bar Todo');
        });
    }

    /** @test */
    public function it_can_load_pipes_from_files()
    {
        Pipe::fake();

        Pipe::namespace('Test')->group(__DIR__ . '/Fixtures/pipes.php');

        $this->pipe(['test' => 'ping']);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                    ->assertSee('pong');
        });
    }
}
