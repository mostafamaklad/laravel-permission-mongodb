<?php

namespace Maklad\Permission\Exceptions;

use InvalidArgumentException;
use Maklad\Permission\Helpers;

class RoleDoesNotExist extends InvalidArgumentException
{
    public static function create(string $roleName)
    {
        return new static(Helpers::logAlertMessage("There is no role named `{$roleName}`."));
    }
}
