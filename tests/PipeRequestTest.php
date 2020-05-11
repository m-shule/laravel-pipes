<?php

namespace Mshule\LaravelPipes\Tests;

use Illuminate\Support\Facades\Event;
use Mshule\LaravelPipes\Events\IncomingPipeResponse;
use Mshule\LaravelPipes\Exceptions\NotFoundPipeException;
use Mshule\LaravelPipes\Facades\Pipe;
use Mshule\LaravelPipes\Testing\MakesPipeRequests;

class PipeRequestTest extends TestCase
{
    use MakesPipeRequests;

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
        $this->withoutExceptionHandling();
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
    public function its_dynamic_pattern_match_checks_first_for_placeholder_name_in_request_then_for_default_cue_value()
    {
        Pipe::fake();

        Pipe::match('trigger:{text}', function ($text) {
            return "you said {$text}";
        });

        $this->pipe(['trigger' => 'something']);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                ->assertSee('you said something');
        });

        Event::listen(IncomingPipeResponse::class, function () {
            $this->pipe(['trigger' => 'something', 'text' => 'another']);

            Pipe::assertResponded(function ($response) {
                $response->assertOk()
                    ->assertSee('you said another');
            });
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
    public function it_doesnt_match_dynamic_parameters_if_the_request_contains_only_parts_of_the_cue()
    {
        Pipe::fake();

        Pipe::any('name {text}', function ($text) {
            return "you said {$text}";
        });

        $this->pipe(['bla' => 'nam', 'text' => 'something']);

        Pipe::assertResponded(function ($response) {
            $response->assertNotFound();
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
    public function it_can_identify_aliases_of_pipes_even_when_the_request_does_only_start_with_the_alias()
    {
        Pipe::fake();

        Pipe::any('mshule', function () {
            return 'matched';
        })->alias(['mhule', 'mule']);

        $this->pipe(['foo' => 'mule1']);

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
    public function it_can_load_pipes_from_files()
    {
        Pipe::fake();

        Pipe::namespace('Test')->group(__DIR__.'/Fixtures/pipes.php');

        $this->pipe(['test' => 'ping']);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                    ->assertSee('pong');
        });
    }

    /** @test */
    public function it_ignores_global_helper_functions_as_cue()
    {
        Pipe::fake();

        Pipe::key('text')->group(function () {
            Pipe::match('report', function () {
                return 'it ignored report()';
            });
        });

        $this->pipe(['text' => 'report']);

        Pipe::assertResponded(function ($response) {
            $response->assertOk()
                    ->assertSee('it ignored report()');
        });
    }

    /** @test */
    public function it_does_not_match_one_character_to_a_pipe_if_it_is_no_dynamic_param()
    {
        Pipe::fake();

        Pipe::key('text')->group(function () {
            Pipe::match('config', function () {
                return 'config pipe triggered';
            });
        });

        $this->pipe(['text' => 'c']);

        Pipe::assertResponded(function ($response) {
            $response->assertNotFound();
        });
    }
}
