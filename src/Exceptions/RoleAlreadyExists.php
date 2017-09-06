<?php

namespace Maklad\Permission\Exceptions;

use InvalidArgumentException;

class RoleAlreadyExists extends InvalidArgumentException
{
    public static function create(string $roleName, string $guardName)
    {
        $message = new static("A role `{$roleName}` already exists for guard `{$guardName}`.");

        if (config('permission.log_registration_exception')) {
            $logger = app('log');
            $logger->alert($message);
        }

        return $message;
    }
}
