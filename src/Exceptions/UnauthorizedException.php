<?php

namespace Maklad\Permission\Exceptions;

use http\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class UnauthorizedException
 * @package Maklad\Permission\Exceptions
 */
class UnauthorizedException extends HttpException
{

    /**
     * UnauthorizedException constructor.
     *
     * @param $statusCode
     * @param null $message
     */
    public function __construct($statusCode, $message = null)
    {
        parent::__construct($statusCode, $message);

        if (\config('permission.log_registration_exception')) {
            $logger = \app('log');
            $logger->alert($message);
        }
    }
}
