<?php

namespace Maklad\Permission\Exceptions;

use InvalidArgumentException;
use Maklad\Permission\Helpers;

class RoleAlreadyExists extends InvalidArgumentException
{
    public static function create(string $roleName, string $guardName)
    {
        return new self(Helpers::logAlertMessage("A role `{$roleName}` already exists for guard `{$guardName}`."));
    }
}
