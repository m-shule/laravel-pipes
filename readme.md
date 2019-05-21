# laravel-pipes

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://travis-ci.org/m-shule/laravel-pipes.svg?branch=master)](https://travis-ci.org/m-shule/laravel-pipes)
[![codecov](https://codecov.io/gh/m-shule/laravel-pipes/branch/master/graph/badge.svg)](https://codecov.io/gh/m-shule/laravel-pipes)
[![Total Downloads](https://img.shields.io/packagist/dt/mshule/laravel-pipes.svg?style=flat-square)](https://packagist.org/packages/mshule/laravel-pipes)

Handling notifications from API's is simple when they only requrire to handle one type of notification, but if you have to handle multiple requests e.g. from an SMS API it can get messy. Similiar to [Botman](https://botman.io)'s `hear()` method, this package provides a similiar approach and could be used as part of another Botman like implementation. Instead of implementing a different approach as you are used to from the [Laravel Routes](https://laravel.com/docs/5.8/routing), laravel-pipes offers a similiar API.

## Install

`composer require mshule/laravel-pipes`

The incoming web request will be handled by `your_app_domain` + whatever you put in the `pipes.incoming_request_path` config. By default the path will result into `your_app_domain/handle-notification`.

**Optional: Create a separate route file for your pipes.**

1. add a new file `routes/pipes.php`
2. set the `pipes.load_routes_file` to `true`

## Usage

To get an overview of all functionalities this package offers, you can check the `tests/PipeRequestTest.php`.

### Handling Pipes

Pipes are matched by the keys and values of the request's data attributes.

```php
// define pipe match for `foo` => `bar`
// key, value, action
Pipe::match('foo', 'bar', function () {
  return 'matched';
});

// same as
Pipe::match('foo:bar', function () {
  return 'matched';
})

$this->pipe(['foo' => 'bar'])
  ->assertSee('matched'); // true
```

Attributes can be bound danymically to the pipe-request.

```php
Pipe::match('foo:{bar}', function ($bar) {
  return $bar;
});

$this->pipe(['foo' => 'bar'])
  ->assertSee('bar'); // true

$this->pipe(['foo' => 'other'])
  ->assertSee('other'); // true
```

Instead of handling all pipe requests inside a callback, you can also redirect to a controller action.

```php
Pipe::match('foo:{bar}', 'SomeController@index');
```

If you want to handle multiple requests with different attribute keys you can use the `Pipe::any()` method.

```php
Pipe::any('{bar}', 'SomeController@index');
```

### Other Options

**alias()**
Sometimes user might have a typo in their message or you simply want to have different cues available to trigger a Pipe.

```php
Pipe::any('bar', 'FooBarController')
  ->alias(['ba', 'b-r', 'bas']);
```

The `FooBarController` will now be called upon `ba`, `b-r`, `bas` or as originally intended on `bar`.

**namespace()**
As you have probably noted the `routes/pipes.php` file is bound to a namespace configurable in the `config/pipes.php`. If you want to define a group with a different namespace, you can use the `namespace()` method:

```php
Pipe::middleware('pipe')
  ->namespace(config('pipes.namespace'))
  ->group(function () {
    // define your namespaced pipes here
  });
```

**key()**
Like demonstrated in the first section of the *Handling Pipes* documentation, you can define Pipe routes in man different ways.

```php
Pipe::match('foo', 'bar', function () {});

// same as
Pipe::match('foo:bar', function () {});
```

There is a third option to specify the `key` of a Pipe by using the `key()` method.

```php
Pipe::key('foo')->match('bar', function () {});
```

The key method is handy if you have got several pipe routes which reacts to the same key.

```php
Pipe::key('text')
  ->group(function () {
    // all pipe definitions within here will check for the `text` as key in the incoming request
    Pipe::match('some-text', function () {});
  });
```

**where()**
To further specify which request should be send to a specific handler you can define conditions on each pipe, like you are used to with [Laravel routes](https://laravel.com/docs/5.8/routing#parameters-regular-expression-constraints).

```php
Pipe::any('{foo}', function ($foo) {
  return $foo;
})->where('foo', 'bar');

Pipe::any('{foo}', function ($foo) {
  return $foo;
})->where('foo', '[a-z]+');
```

**Understanding Pipe Life Cycle**

The laravel-pipes lifecycle starts with a `post` request which is send to the `pipes.incoming_request_path`. The `ExecutePipeRequest` Job is dispatched and a http response returned - this is important, since the pipe request is handled asynchronously if you have another queue driver than `sync`. In the Job the `$request` is passed to the Pipe-Kernel's `handle()` method where it is passed through the global pipe-middlewares. The request is matched with the registered pipes and if a match is found the response is returned, otherwise a `NotFoundPipeException` is thrown.

**Testing Pipes**

This package provides a simple trait to perform pipe requests. The `MakesPipeRequests` Trait provides a `pipe()` method to perform a pipe-request. The method fires a `post` request to the specified endpoint in `pipes.incoming_request_path`, but it is much easier to write `$this->pipe(...)` than `$this->post(config('pipes.incoming_request_path), [...])`.

Since the pipe request is executed trough a job, you have to use the `Pipe::fake()` method to get access to your responses.

```php
Pipe::fake();

$this->pipe(...);

Pipe::assertResponded(function ($response) {
  $response->assertOk()
    ->assertSee(...);
});
```

Behind the scenes the `Pipe::fake()` method simply triggers the `Event::fake()` with the `IncomingPipeRequest` and `IncomingPipeResonse` events.

## Testing

Run the tests with:

```bash
vendor/bin/phpunit
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email DummyAuthorEmail instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](/LICENSE.md) for more information.
