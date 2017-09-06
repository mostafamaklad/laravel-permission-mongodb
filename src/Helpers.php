<?php

namespace Maklad\Permission;

use Illuminate\Support\Collection;

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
     * @param string $expected
     * @param string $given
     *
     * @return string
     */
    public static function getGuardDoesNotMatchMessage(Collection $expected, string $given): string
    {
        return "The given role or permission should use guard `{$expected->implode(', ')}` instead of `{$given}`.";
    }

    /**
     * @param string $name
     * @param string $guardName
     *
     * @return string
     */
    public static function getPermissionAlreadyExistsMessage(string $name, string $guardName): string
    {
        return "A permission `{$name}` already exists for guard `{$guardName}`.";
    }

    /**
     * @param string $name
     * @param string $guardName
     *
     * @return string
     */
    public static function getPermissionDoesNotExistMessage(string $name, string $guardName): string
    {
        return "There is no permission named `{$name}` for guard `{$guardName}`.";
    }

    /**
     * @param string $name
     * @param string $guardName
     *
     * @return string
     */
    public static function getRoleAlreadyExistsMessage(string $name, string $guardName): string
    {
        return "A role `{$name}` already exists for guard `{$guardName}`.";
    }

    /**
     * @param string $name
     *
     * @param string $guardName
     *
     * @return string
     */
    public static function getRoleDoesNotExistMessage(string $name, string $guardName): string
    {
        return "There is no role named `{$name}` for guard `{$guardName}`.";
    }
}
