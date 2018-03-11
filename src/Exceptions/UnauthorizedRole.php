<?php

namespace Maklad\Permission\Exceptions;

/**
 * Class UnauthorizedRole
 * @package Maklad\Permission\Exceptions
 */
class UnauthorizedRole extends UnauthorizedException
{
    /**
     * UnauthorizedPermission constructor.
     *
     * @param $statusCode
     * @param null $message
     * @param array $requiredRoles
     */
    public function __construct($statusCode, $message = null, $requiredRoles = [])
    {
        parent::__construct($statusCode, $message, $requiredRoles);
    }
}
