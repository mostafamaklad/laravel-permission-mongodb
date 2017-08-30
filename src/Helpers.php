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
}
