<?php
declare(strict_types=1);

namespace Maklad\Permission\Traits;

use Illuminate\Support\Collection;
use Jenssegers\Mongodb\Eloquent\Builder;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Relations\BelongsToMany;
use Maklad\Permission\Contracts\PermissionInterface as Permission;
use Maklad\Permission\Exceptions\GuardDoesNotMatch;
use Maklad\Permission\Guard;
use Maklad\Permission\Helpers;
use Maklad\Permission\Models\Role;
use Maklad\Permission\PermissionRegistrar;
use Maklad\Permission\Models\RoleAssignment;
use Metabuyer\Models\Users;

/**
 * Trait HasPermissions
 * @package Maklad\Permission\Traits
 */
trait HasPermissions
{
    public static function bootHasPermissions()
    {
        static::deleting(function (Model $model) {
            if (isset($model->forceDeleting) && !$model->forceDeleting) {
                return;
            }

            $model->permissions()->sync([]);
        });
    }

    /**
     * A role may be given various permissions.
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.collection_names.role_has_permissions')
        );
    }

    /**
     * A role belongs to some users of the model associated with its guard.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany($this->helpers->getModelForGuard($this->attributes['guard_name']));
    }

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
        $permissions = collect($permissions)
            ->flatten()
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
        $this->permissions()->sync([]);

        return $this->givePermissionTo($permissions);
    }

    /**
     * Revoke the given permission.
     *
     * @param string|array|Permission|\Illuminate\Support\Collection $permissions
     *
     * @return $this
     * @throws \Maklad\Permission\Exceptions\GuardDoesNotMatch
     */
    public function revokePermissionTo(...$permissions)
    {
        collect($permissions)
            ->flatten()
            ->map(function ($permission) {
                $permission = $this->getStoredPermission($permission);
                $this->permissions()->detach($permission);

                return $permission;
            });

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * @param string|Permission $permission
     *
     * @return Permission
     * @throws \ReflectionException
     */
    protected function getStoredPermission($permission): Permission
    {
        if (\is_string($permission)) {
            return \app(Permission::class)->findByName($permission, $this->getDefaultGuardName());
        }

        return $permission;
    }

    /**
     * @param Model $roleOrPermission
     *
     * @throws GuardDoesNotMatch
     * @throws \ReflectionException
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

    /**
     * @return Collection
     * @throws \ReflectionException
     */
    protected function getGuardNames(): Collection
    {
        return (new Guard())->getNames($this);
    }

    /**
     * @return string
     * @throws \ReflectionException
     */
    protected function getDefaultGuardName(): string
    {
        return (new Guard())->getDefaultName($this);
    }

    /**
     * Forget the cached permissions.
     */
    public function forgetCachedPermissions()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Convert to Permission Models
     *
     * @param string|array|Collection $permissions
     *
     * @return Collection
     */
    private function convertToPermissionModels($permissions): Collection
    {
        if (\is_array($permissions)) {
            $permissions = collect($permissions);
        }

        if (! $permissions instanceof Collection) {
            $permissions = collect([$permissions]);
        }

        $permissions = $permissions->map(function ($permission) {
            return $this->getStoredPermission($permission);
        });

        return $permissions;
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
        return $this->load('roles', 'roles.permissions')
            ->roles->flatMap(function (Role $role) {
                return $role->permissions;
            })->sort()->values();
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
     * @param string|Permission $permission
     * @param string|null $guardName
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if (\is_string($permission)) {
            $permission = \app(Permission::class)->findByName(
                $permission,
                $guardName ?? $this->getDefaultGuardName()
            );
        }

        return $this->hasDirectPermission($permission) || $this->hasPermissionViaRole($permission);
    }

    public function getPermissionOf($permission, $guardName = null, $org_id = null)
    {
        if ($this instanceof Users) {
            $roleAssignment_datas = RoleAssignment::where('organization_id', $org_id)->get();
            $user = $this->toArray();

            foreach ($roleAssignment_datas as $roleAssignment_data) {
                if (array_key_exists($roleAssignment_data->_id, $user)) {
                    $role_assignment_id = $roleAssignment_data->_id;
                }
            }

            foreach ($user[$role_assignment_id]['role_id'] as $key => $value) {
                $role = Role::where('_id', $value)->first();
                if ($role->hasPermissionTo($permission)) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return $this->hasPermissionTo($permission);
        }
    }

    /**
     * Determine if the model has any of the given permissions.
     *
     * @param array ...$permissions
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function hasAnyPermission(...$permissions): bool
    {
        if (\is_array($permissions[0])) {
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
     * @throws \ReflectionException
     */
    public function hasDirectPermission($permission): bool
    {
        if (\is_string($permission)) {
            $permission = \app(Permission::class)->findByName($permission, $this->getDefaultGuardName());
        }

        return $this->permissions->contains('id', $permission->id);
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
     * @param string|array|Permission|Collection $permissions
     *
     * @return Builder
     */
    public function scopePermission(Builder $query, $permissions): Builder
    {
        $permissions = $this->convertToPermissionModels($permissions);

        $roles = \collect([]);

        foreach ($permissions as $permission) {
            $roles = $roles->merge($permission->roles);
        }
        $roles = $roles->unique();

        return $query->orWhereIn('permission_ids', $permissions->pluck('_id'))
            ->orWhereIn('role_ids', $roles->pluck('_id'));
    }
}
