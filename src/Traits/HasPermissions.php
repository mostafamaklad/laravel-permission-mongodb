<?php

namespace Maklad\Permission\Traits;

use Illuminate\Support\Collection;
use MongoDB\Laravel\Eloquent\Builder;
use MongoDB\Laravel\Eloquent\Model;
use Maklad\Permission\Contracts\PermissionInterface;
use Maklad\Permission\Contracts\PermissionInterface as Permission;
use Maklad\Permission\Exceptions\GuardDoesNotMatch;
use Maklad\Permission\Guard;
use Maklad\Permission\Helpers;
use Maklad\Permission\PermissionRegistrar;
use ReflectionException;
use function collect;
use function is_array;
use function is_string;

/**
 * Trait HasPermissions
 * @package Maklad\Permission\Traits
 */
trait HasPermissions
{
    private $permissionClass;

    public function getPermissionClass()
    {
        if ($this->permissionClass === null) {
            $this->permissionClass = app(PermissionRegistrar::class)->getPermissionClass();
        }
        return $this->permissionClass;
    }

    /**
     * Query the permissions
     */
    public function permissionsQuery()
    {
        $permission = $this->getPermissionClass();
        return $permission::whereIn('_id', $this->permission_ids ?? []);
    }

    /**
     * gets the permissions Attribute
     */
    public function getPermissionsAttribute()
    {
        return $this->permissionsQuery()->get();
    }

    /**
     * Grant the given permission(s) to a role.
     *
     * @param string|array|Permission|Collection $permissions
     *
     * @return $this
     * @throws GuardDoesNotMatch
     */
    public function givePermissionTo(...$permissions): self
    {
        $this->permission_ids = collect($this->permission_ids ?? [])
            ->merge($this->getPermissionIds($permissions))
            ->all();

        $this->save();

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Remove all current permissions and set the given ones.
     *
     * @param string|array|Permission|Collection $permissions
     *
     * @return $this
     * @throws GuardDoesNotMatch
     */
    public function syncPermissions(...$permissions): self
    {
        $this->permission_ids = $this->getPermissionIds($permissions);

        $this->save();
        return $this->givePermissionTo($permissions);
    }

    /**
     * Revoke the given permission.
     *
     * @param string|array|Permission|Collection $permissions
     *
     * @return $this
     * @throws GuardDoesNotMatch
     */
    public function revokePermissionTo(...$permissions): self
    {
        $permissions = $this->getPermissionIds($permissions);

        $this->permission_ids = collect($this->permission_ids ?? [])
            ->filter(function ($permission) use ($permissions) {
                return ! in_array($permission, $permissions, true);
            })
            ->all();

        $this->save();

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * @param string|Permission $permission
     *
     * @return Permission
     * @throws ReflectionException
     */
    protected function getStoredPermission($permission): Permission
    {
        if (is_string($permission)) {
            return $this->getPermissionClass()->findByName($permission, $this->getDefaultGuardName());
        }

        return $permission;
    }

    /**
     * @param Model $roleOrPermission
     *
     * @throws GuardDoesNotMatch
     * @throws ReflectionException
     */
    protected function ensureModelSharesGuard(Model $roleOrPermission): void
    {
        if (! $this->getGuardNames()->contains($roleOrPermission->guard_name)) {
            $expected = $this->getGuardNames();
            $given    = $roleOrPermission->guard_name;
            $helpers  = new Helpers();

            throw new GuardDoesNotMatch($helpers->getGuardDoesNotMatchMessage($expected, $given));
        }
    }

    /**
     * @return Collection
     * @throws ReflectionException
     */
    protected function getGuardNames(): Collection
    {
        return (new Guard())->getNames($this);
    }

    /**
     * @return string
     * @throws ReflectionException
     */
    protected function getDefaultGuardName(): string
    {
        return (new Guard())->getDefaultName($this);
    }

    /**
     * Forget the cached permissions.
     */
    public function forgetCachedPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Convert to Permission Models
     *
     * @param array|string|Collection $permissions
     *
     * @return Collection
     */
    private function convertToPermissionModels($permissions): Collection
    {
        if (is_array($permissions)) {
            $permissions = collect($permissions);
        }

        if (! $permissions instanceof Collection) {
            $permissions = collect([$permissions]);
        }

        return $permissions->map(function ($permission) {
            return $this->getStoredPermission($permission);
        });
    }

    /**
     * Return a collection of permission names associated with this user.
     *
     * @return Collection
     */
    public function getPermissionNames(): Collection
    {
        return $this->getAllPermissions()->pluck('name');
    }

    /**
     * Return all the permissions the model has via roles.
     */
    public function getPermissionsViaRoles(): Collection
    {
        $permissionIds = $this->roles->pluck('permission_ids')->flatten()->unique()->values();
        return $this->getPermissionClass()->query()->whereIn('_id', $permissionIds)->get();
        /*return $this->load('roles', 'roles.permissions')
            ->roles->flatMap(function (Role $role) {
                return $role->permissions;
            })->sort()->values();*/
    }

    /**
     * Return all the permissions the model has, both directly and via roles.
     */
    public function getAllPermissions(): Collection
    {
        return $this->permissions
            ->merge($this->getPermissionsViaRoles())
            ->sort()
            ->values();
    }

    /**
     * Determine if the model may perform the given permission.
     *
     * @param string|PermissionInterface $permission
     * @param string|null $guardName
     * @return bool
     * @throws ReflectionException
     */
    public function hasPermissionTo($permission, string $guardName = null): bool
    {
        if (is_string($permission)) {
            $permission = $this->getPermissionClass()->findByName(
                $permission,
                $guardName ?? $this->getDefaultGuardName()
            );
        }

        return $this->hasDirectPermission($permission) || $this->hasPermissionViaRole($permission);
    }

    /**
     * Determine if the model has any of the given permissions.
     *
     * @param array ...$permissions
     *
     * @return bool
     * @throws ReflectionException
     */
    public function hasAnyPermission(...$permissions): bool
    {
        if (is_array($permissions[0])) {
            $permissions = $permissions[0];
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the model has all the given permissions(s).
     *
     * @param $permissions
     *
     * @return bool
     * @throws ReflectionException
     */
    public function hasAllPermissions(...$permissions): bool
    {
        $helpers = new Helpers();
        $permissions = $helpers->flattenArray($permissions);

        foreach ($permissions as $permission) {
            if (!$this->hasPermissionTo($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Determine if the model has, via roles, the given permission.
     *
     * @param Permission $permission
     *
     * @return bool
     */
    protected function hasPermissionViaRole(Permission $permission): bool
    {
        return $this->hasRole($permission->roles);
    }

    /**
     * Determine if the model has the given permission.
     *
     * @param string|Permission $permission
     *
     * @return bool
     * @throws ReflectionException
     */
    public function hasDirectPermission($permission): bool
    {
        if (is_string($permission)) {
            $permission = $this->getPermissionClass()->findByName($permission, $this->getDefaultGuardName());
        }

        return $this->permissions->contains('_id', $permission->_id);
    }

    /**
     * Return all permissions the directory coupled to the model.
     */
    public function getDirectPermissions(): Collection
    {
        return $this->permissions;
    }

    /**
     * Scope the model query to certain permissions only.
     *
     * @param Builder $query
     * @param array|string|Permission|Collection $permissions
     *
     * @return Builder
     */
    public function scopePermission(Builder $query, $permissions): Builder
    {
        $permissions = $this->convertToPermissionModels($permissions);

        $roles = collect([]);

        foreach ($permissions as $permission) {
            $roles = $roles->merge($permission->roles);
        }
        $roles = $roles->unique();

        return $query->orWhereIn('permission_ids', $permissions->pluck('_id'))
            ->orWhereIn('role_ids', $roles->pluck('_id'));
    }

    /**
     * @param string|array|Permission|Collection $permissions
     * @return array
     */
    protected function getPermissionIds(...$permissions): array
    {
        return collect($permissions)
            ->flatten()
            ->map(function ($permission) {
                $permission = $this->getStoredPermission($permission);
                $this->ensureModelSharesGuard($permission);
                return $permission->_id;
            })
            ->all();
    }
}
