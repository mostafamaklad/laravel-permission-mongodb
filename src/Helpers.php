<?php

namespace Maklad\Permission;

class Helpers
{
    /**
     * @param string $guard
     *
     * @return string|null
     */
    public static function getModelForGuard(string $guard)
    {
        return collect(config('auth.guards'))
            ->map(function ($guard) {
                return config("auth.providers.{$guard['provider']}.model");
            })->get($guard);
    }

    /**
     * Log Alert Message
     * @param string $message
     *
     * @return string
     */
    public static function logAlertMessage(string $message):string
    {
        if (config('permission.log_registration_exception')) {
            $logger = app('log');
            $logger->alert($message);
        }

        return $message;
    }
}
