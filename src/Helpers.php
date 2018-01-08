<?php
declare(strict_types=1);

namespace Maklad\Permission;

use Illuminate\Support\Collection;

/**
 * Class Helpers
 * @package Maklad\Permission
 */
class Helpers
{
    /**
     * @param string $guard
     *
     * @return string|null
     */
    public function getModelForGuard(string $guard)
    {
        return \collect(\config('auth.guards'))
            ->map(function ($guard) {
                return \config("auth.providers.{$guard['provider']}.model");
            })->get($guard);
    }

    /**
     * @param Collection $expected
     * @param string $given
     *
     * @return string
     */
    public function getGuardDoesNotMatchMessage(Collection $expected, string $given): string
    {
        return "The given role or permission should use guard `{$expected->implode(', ')}` instead of `{$given}`.";
    }

    /**
     * @param string $name
     * @param string $guardName
     *
     * @return string
     */
    public function getPermissionAlreadyExistsMessage(string $name, string $guardName): string
    {
        return "A permission `{$name}` already exists for guard `{$guardName}`.";
    }

    /**
     * @param string $name
     * @param string $guardName
     *
     * @return string
     */
    public function getPermissionDoesNotExistMessage(string $name, string $guardName): string
    {
        return "There is no permission named `{$name}` for guard `{$guardName}`.";
    }

    /**
     * @param string $name
     * @param string $guardName
     *
     * @return string
     */
    public function getRoleAlreadyExistsMessage(string $name, string $guardName): string
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
    public function getRoleDoesNotExistMessage(string $name, string $guardName): string
    {
        return "There is no role named `{$name}` for guard `{$guardName}`.";
    }

    /**
     * @param string $roles
     *
     * @return string
     */
    public function getUnauthorizedRoleMessage(string $roles): string
    {
        return "User does not have the right roles `{$roles}`.";
    }

    /**
     * @param string $permissions
     *
     * @return string
     */
    public function getUnauthorizedPermissionMessage(string $permissions): string
    {
        return "User does not have the right permissions `{$permissions}`.";
    }

    /**
     * @return string
     */
    public function getUserNotLoggedINMessage(): string
    {
        return 'User is not logged in.';
    }
}
