<?php

namespace Maklad\Permission\Exceptions;

use InvalidArgumentException;
use Maklad\Permission\Helpers;

class PermissionAlreadyExists extends InvalidArgumentException
{
    public static function create(string $permissionName, string $guardName)
    {
        $message = "A permission `{$permissionName}` already exists for guard `{$guardName}`.";

        return new static(Helpers::logAlertMessage($message));
    }
}
