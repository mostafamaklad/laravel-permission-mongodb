<?php

namespace Maklad\Permission\Exceptions;

use InvalidArgumentException;
use Maklad\Permission\Helpers;

class PermissionDoesNotExist extends InvalidArgumentException
{
    public static function create(string $permissionName, string $guardName = '')
    {
        $message = "There is no permission named `{$permissionName}` for guard `{$guardName}`.";

        return new self(Helpers::logAlertMessage($message));
    }
}
