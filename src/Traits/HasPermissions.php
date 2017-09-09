<?php
declare(strict_types=1);

namespace Maklad\Permission\Traits;

use Illuminate\Support\Collection;
use Jenssegers\Mongodb\Eloquent\Model;
use Maklad\Permission\Contracts\PermissionInterface as Permission;
use Maklad\Permission\Exceptions\GuardDoesNotMatch;
use Maklad\Permission\Helpers;
use Maklad\Permission\PermissionRegistrar;

/**
 * Trait HasPermissions
 * @package Maklad\Permission\Traits
 */
trait HasPermissions
{
    /**
     * Grant the given permission(s) to a role.
     *
     * @param string|array|Permission|\Illuminate\Support\Collection $permissions
     *
     * @return $this
     * @throws GuardDoesNotMatch
     */
    public function givePermissionTo(...$permissions)
    {
        $permissions = new Collection($permissions);
        $permissions = $permissions->flatten()
                                   ->map(function ($permission) {
                                       return $this->getStoredPermission($permission);
                                   })
                                   ->each(function ($permission) {
                                       $this->ensureModelSharesGuard($permission);
                                   })
                                   ->all();

        $this->permissions()->saveMany($permissions);

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Remove all current permissions and set the given ones.
     *
     * @param string|array|Permission|\Illuminate\Support\Collection $permissions
     *
     * @return $this
     * @throws GuardDoesNotMatch
     */
    public function syncPermissions(...$permissions)
    {
        $this->permissions()->detach();

        return $this->givePermissionTo($permissions);
    }

    /**
     * Revoke the given permission.
     *
     * @param Permission|string $permission
     *
     * @return $this
     */
    public function revokePermissionTo($permission)
    {
        $this->permissions()->detach($this->getStoredPermission($permission));

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * @param string|Permission $permissions
     *
     * @return Permission
     */
    protected function getStoredPermission($permissions): Permission
    {
        $helpers = new Helpers();
        if (\is_string($permissions)) {
            return $helpers->app(Permission::class)->findByName($permissions, $this->getDefaultGuardName());
        }

        return $permissions;
    }

    /**
     * @param Model $roleOrPermission
     *
     * @throws GuardDoesNotMatch
     */
    protected function ensureModelSharesGuard(Model $roleOrPermission)
    {
        if (! $this->getGuardNames()->contains($roleOrPermission->guard_name)) {
            $expected = $this->getGuardNames();
            $given    = $roleOrPermission->guard_name;
            $helpers  = new Helpers();

            throw new GuardDoesNotMatch($helpers->getGuardDoesNotMatchMessage($expected, $given));
        }
    }

    protected function getGuardNames(): Collection
    {
        $helpers = new Helpers();
        if ($this->guard_name) {
            return new Collection($this->guard_name);
        }

        $guards = new Collection($helpers->config('auth.guards'));

        return $guards->map(function ($guard) {
            $helpers = new Helpers();

            return $helpers->config("auth.providers.{$guard['provider']}.model");
        })
                      ->filter(function ($model) {
                          return \get_class($this) === $model;
                      })
                      ->keys();
    }

    protected function getDefaultGuardName(): string
    {
        $helpers = new Helpers();
        $default = $helpers->config('auth.defaults.guard');

        return $this->getGuardNames()->first() ?: $default;
    }

    /**
     * Forget the cached permissions.
     */
    public function forgetCachedPermissions()
    {
        $helpers = new Helpers();
        $helpers->app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
