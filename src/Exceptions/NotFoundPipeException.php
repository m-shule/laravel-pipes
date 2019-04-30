<?php

namespace Mshule\LaravelPipes\Exceptions;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NotFoundPipeException extends HttpException
{
    /**
     * @param Request    $request
     * @param \Exception $previous
     * @param int|null   $code
     * @param array      $headers
     */
    public function __construct(Request $request, \Exception $previous = null, ?int $code = 0, array $headers = [])
    {
        parent::__construct(404, "{$request->method()} {$request->url()}", $previous, $headers, $code);
    }
}
