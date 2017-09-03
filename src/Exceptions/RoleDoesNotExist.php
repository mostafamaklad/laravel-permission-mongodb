<?php

namespace Maklad\Permission\Exceptions;

use InvalidArgumentException;

class RoleDoesNotExist extends InvalidArgumentException
{
    public static function create(string $roleName)
    {
        $message = new static("There is no role named `{$roleName}`.");

        if (config('permission.log_registration_exception')) {
            $logger = app('log');
            $logger->alert($message);
        }

        return $message;
    }
}
