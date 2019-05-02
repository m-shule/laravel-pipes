# laravel-pipes

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Travis](https://img.shields.io/travis/mshule/laravel-pipes.svg?style=flat-square)]()
[![Total Downloads](https://img.shields.io/packagist/dt/mshule/laravel-pipes.svg?style=flat-square)](https://packagist.org/packages/mshule/laravel-pipes)

Handling notifications from API's is simple when they only requrire to handle one type of notification, but if you have to handle multiple requests e.g. from an SMS API it can get messy. Similiar to [Botman](botman.io)'s `hear()` method, this package provides a similiar approach and could be used as part of another Botman like implementation. Instead of implementing a different approach as you are used to from the [Laravel Routes](https://laravel.com/docs/5.8/routing), laravel-pipes offers a similiar API.

## Install

`composer require mshule/laravel-pipes`

The incoming web request will be handled by `your_app_domain` + whatever you put in the `pipes.incoming_request_path` config. By default the path will result into `your_app_domain/handle-notification`.

**Optional: Create a separate route file for your pipes.**

1. add a new file `routes/pipes.php`
2. set the `pipes.load_routes_file` to `true`

## Usage

To get an overview of all functionalities this package offers, you can check the `tests/PipeRequestTest.php`.

**Handling Pipes**

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

**Testing Pipes**

This package provides a simple trait to perform pipe requests. The `MakesPipeRequests` Trait provides a `pipe()` method to perform a pipe-request. The method fires a `post` request to the specified endpoint in `pipes.incoming_request_path`, but it is much easier to write `$this->pipe(...)` than `$this->post(config('pipes.incoming_request_path), [...])`.

**Understanding Pipe Life Cycle**

The laravel-pipes lifecycle starts with a `post` request which is send to the `pipes.incoming_request_path`. The `$request` is passed to the Pipe-Kernel's `handle()` method where it is passed through the global pipe-middlewares. The request is matched with the registered pipes and if a match is found the response is returned, otherwise a `NotFoundPipeException` is thrown, which is converted into a 404 error.

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
