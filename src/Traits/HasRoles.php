<?php
declare(strict_types=1);

namespace Maklad\Permission\Traits;

use Illuminate\Support\Collection;
use Jenssegers\Mongodb\Eloquent\Builder;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Relations\BelongsToMany;
use Maklad\Permission\Contracts\PermissionInterface as Permission;
use Maklad\Permission\Contracts\RoleInterface as Role;
use Maklad\Permission\Models\Organization;
use Maklad\Permission\Models\RoleAssignment;
use ReflectionException;

/**
 * Trait HasRoles
 * @package Maklad\Permission\Traits
 */
trait HasRoles
{
    use HasPermissions;

    public static function bootHasRoles()
    {
        static::deleting(function (Model $model) {
            if (isset($model->forceDeleting) && !$model->forceDeleting) {
                return;
            }

            $model->roles()->sync([]);
        });
    }

    /**
     * A model may have multiple roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(\config('permission.models.role'))->withTimestamps();
    }

    /**
     * Scope the model query to certain roles only.
     *
     * @param Builder $query
     * @param string|array|Role|Collection $roles
     *
     * @return Builder
     */
    public function scopeRole(Builder $query, $roles): Builder
    {
        $roles = $this->convertToRoleModels($roles);

        return $query->whereIn('role_ids', $roles->pluck('_id'));
    }

    /**
     * Assign the given role to the Role Assignment.
     *
     * @param array|string|Role ...$roles
     *
     * @return array|Role|string
     */
    public function assignRole(...$roles)
    {
        $class = get_class($this);
        $organization = \app(Organization::class)->where('class', $class)->first();

        $roleAssignment = \app(RoleAssignment::class)->where('organization_id', $organization->_id)->first();

        if (empty($roleAssignment)) {
            $roleAssignment = \app(RoleAssignment::class)->create([
                'organization_id' => $organization->_id,
                'weight' => $organization->weight
            ]);
        }

        $roles = \collect($roles)
            ->flatten()
            ->map(function ($role) {
                return $this->getStoredRole($role);
            })
            ->each(function ($role) {
                $this->ensureModelSharesGuard($role);
            })
            ->all();

        $roleAssignment->roles()->saveMany($roles);

        $roleAssignment->forgetCachedPermissions();

        return $roles;
    }

    /**
     * Prepare role data with assign permissions.
     *
     * @param $role
     * @return array
     */
    public function prepareRoles($role)
    {
        $roleData = [];

        if (empty($role)) {
            $roleData;
        }

        $roleData['_id'] = $role['_id'];
        $roleData['name'] = $role['name'];
        $roleData['guard_name'] = $role['guard_name'];

        $allPermissions = [];
        foreach ($role['permission_ids'] as $key => $value) {
            $permission = \app(\Maklad\Permission\Models\Permission::class)->where('_id', $value)->first();

            $eachPermission = [];
            $eachPermission['_id'] = $permission->_id;
            $eachPermission['name'] = $permission->name;
            $eachPermission['guard_name'] = $permission->guard_name;
            $allPermissions[] = $eachPermission;
        }
        $roleData['permissions'] = $allPermissions;

        return $roleData;
    }

    /**
     * Assign the given role to the User.
     *
     * @param null $organization
     * @param mixed ...$roles
     * @return bool
     */
    public function assignOrgRole($organization, ...$roles)
    {
        $allRoles = \collect($roles)
            ->flatten()
            ->map(function ($role) {
                $role = $this->getStoredRole($role);

                return $this->prepareRoles($role);
            });

        if(empty($allRoles) || empty($organization)){
            return false;
        }

        $roleIds = [];
        foreach($allRoles as $key => $value){
            $roleIds[] = $value['_id'];
        }

        $organizationId = is_object($organization) ? $organization->_id : $organization;

        $roleAssignment = \app(RoleAssignment::class)
            ->where('organization_id', $organizationId)
            ->whereIn('role_ids', $roleIds)
            ->first();

        if (empty($roleAssignment) ||
            (!empty($this->role_assignment_ids) && in_array($roleAssignment->_id, $this->role_assignment_ids))) {
            return false;
        }

        $roleAssignmentIds = [];
        $roleAssignmentObjs = [];
        if (!empty($this->role_assignment_ids) && !empty($this->role_assignments)) {
            $roleAssignmentIds = $this->role_assignment_ids ?? [];
            $roleAssignmentObjs = $this->role_assignments ?? [];
        }

        $roleAssignmentIds[] = $roleAssignment->_id;
        $this->role_assignment_ids = $roleAssignmentIds;

        $eachRoleAssignment = [];
        $eachRoleAssignment['_id'] = $roleAssignment->_id;
        $eachRoleAssignment['weight'] = $roleAssignment->weight;
        $eachRoleAssignment['organization_id'] = $roleAssignment->organization_id;

        $eachRoleAssignment['roles'] = $allRoles->toArray();
        $roleAssignmentObjs[] = $eachRoleAssignment;
        $this->role_assignments = $roleAssignmentObjs;

        return $this->save();
    }

    /**
     * Revoke the given role from the model.
     *
     * @param array|string|Role ...$roles
     *
     * @return array|Role|string
     */
    public function removeRole(...$roles)
    {
        \collect($roles)
            ->flatten()
            ->map(function ($role) {
                $role = $this->getStoredRole($role);
                $this->roles()->detach($role);

                return $role;
            });

        $this->forgetCachedPermissions();

        return $roles;
    }

    /**
     * Remove all current roles and set the given ones.
     *
     * @param array ...$roles
     *
     * @return $this
     */
    public function syncRoles(...$roles)
    {
        $class = get_class($this);
        $organization = Organization::where('class', $class)->first();
        $roleAssignment = RoleAssignment::where('organization_id', $organization->_id)->first();

        $roleAssignment->roles()->sync([]);

        return $this->assignRole($roles);
    }

    /**
     * Determine if the model has (one of) the given role(s).
     *
     * @param string|array|Role|\Illuminate\Support\Collection $roles
     *
     * @return bool
     */
    public function hasRole($roles): bool
    {
        if (\is_string($roles) && false !== \strpos($roles, '|')) {
            $roles = \explode('|', $roles);
        }

        if (\is_string($roles) || $roles instanceof Role) {
            return $this->roles->contains('name', $roles->name ?? $roles);
        }

        $roles = \collect()->make($roles)->map(function ($role) {
            return $role instanceof Role ? $role->name : $role;
        });

        return ! $roles->intersect($this->roles->pluck('name'))->isEmpty();
    }

    /**
     * Determine if the model has any of the given role(s).
     *
     * @param string|array|Role|\Illuminate\Support\Collection $roles
     *
     * @return bool
     */
    public function hasAnyRole($roles): bool
    {
        return $this->hasRole($roles);
    }

    /**
     * Determine if the model has all of the given role(s).
     *
     * @param string|Role|\Illuminate\Support\Collection $roles
     *
     * @return bool
     */
    public function hasAllRoles($roles): bool
    {
        if (\is_string($roles) && false !== strpos($roles, '|')) {
            $roles = \explode('|', $roles);
        }

        if (\is_string($roles) || $roles instanceof Role) {
            return $this->hasRole($roles);
        }

        $roles = \collect()->make($roles)->map(function ($role) {
            return $role instanceof Role ? $role->name : $role;
        });

        return $roles->intersect($this->roles->pluck('name')) == $roles;
    }

    /**
     * Return Role object
     *
     * @param String|Role $role role name
     *
     * @return Role
     * @throws ReflectionException
     */
    protected function getStoredRole($role): Role
    {
        if (\is_string($role)) {
            return \app(Role::class)->findByName($role, $this->getDefaultGuardName());
        }

        return $role;
    }

    /**
     * Return a collection of role names associated with this user.
     *
     * @return Collection
     */
    public function getRoleNames(): Collection
    {
        return $this->roles()->pluck('name');
    }

    /**
     * Convert to Role Models
     *
     * @param $roles
     *
     * @return Collection
     */
    private function convertToRoleModels($roles): Collection
    {
        if (is_array($roles)) {
            $roles = collect($roles);
        }

        if (!$roles instanceof Collection) {
            $roles = collect([$roles]);
        }

        $roles = $roles->map(function ($role) {
            return $this->getStoredRole($role);
        });

        return $roles;
    }
}
