<?php

namespace Maklad\Permission\Exceptions;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use Maklad\Permission\Helpers;

class GuardDoesNotMatch extends InvalidArgumentException
{
    public static function create(string $givenGuard, Collection $expectedGuards)
    {
        $expected = $expectedGuards->implode(', ');
        $message = "The given role or permission should use guard `{$expected}` instead of `{$givenGuard}`.";
        return new static(Helpers::logAlertMessage($message));
    }
}
