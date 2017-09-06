<?php

namespace Maklad\Permission\Exceptions;

use InvalidArgumentException;

class PermissionDoesNotExist extends InvalidArgumentException
{
    public static function create(string $permissionName, string $guardName = '')
    {
        $message = new static("There is no permission named `{$permissionName}` for guard `{$guardName}`.");

        if (config('permission.log_registration_exception')) {
            $logger = app('log');
            $logger->alert($message);
        }

        return $message;
    }
}
