<?php

namespace Mshule\LaravelPipes\Tests;

use Mshule\LaravelPipes\Facades\Pipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mshule\LaravelPipes\Testing\MakesPipeRequests;
use Mshule\LaravelPipes\Tests\Fixtures\Models\Todo;

class SubstituteBindingsTest extends TestCase
{
    use RefreshDatabase, MakesPipeRequests;

    public function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/Fixtures/Migrations');
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
}
