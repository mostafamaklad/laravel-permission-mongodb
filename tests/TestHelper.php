<?php

namespace Maklad\Permission\Test;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class TestHelper
 * @package Maklad\Permission\Test
 */
class TestHelper
{
    /**
     * @param string $middleware
     * @param object $parameter
     *
     * @return int
     */
    public function testMiddleware($middleware, $parameter)
    {
        try {
            return $middleware->handle(new Request(), function () {
                return (new Response())->setContent('<html></html>');
            }, $parameter)->status();
        } catch (HttpException $exception) {
            return $exception->getStatusCode();
        }
    }
}
