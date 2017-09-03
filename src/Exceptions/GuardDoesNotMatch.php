<?php

namespace Maklad\Permission\Exceptions;

use InvalidArgumentException;
use Illuminate\Support\Collection;

class GuardDoesNotMatch extends InvalidArgumentException
{
    public static function create(string $givenGuard, Collection $expectedGuards)
    {
        $message = new static("The given role or permission should use guard `{$expectedGuards->implode(', ')}` instead of `{$givenGuard}`.");

        if (config('permission.log_registration_exception')) {
            $logger = app('log');
            $logger->alert($message);
        }
        
        return $message;
    }
}
