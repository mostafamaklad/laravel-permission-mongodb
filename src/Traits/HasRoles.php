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
            return $roleData;
        }

        $roleData['_id'] = $role['_id'];
        $roleData['name'] = $role['name'];
        $roleData['guard_name'] = $role['guard_name'];

        $permissions = \app(\Maklad\Permission\Models\Permission::class)
            ->whereIn('_id', $role['permission_ids'])
            ->get(['_id', 'name', 'guard_name'])
            ->toArray();

        $roleData['permissions'] = $permissions;

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
        $roles = \collect($roles)
            ->flatten()
            ->map(function ($role) {
                $role = $this->getStoredRole($role);

                return $this->prepareRoles($role);
            })
            ->toArray();

        if (empty($roles) || empty($organization)) {
            return false;
        }

        $roleIds = array_column($roles, '_id');

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
            $roleAssignmentIds = $this->role_assignment_ids;
            $roleAssignmentObjs = $this->role_assignments;
        }

        $roleAssignmentIds[] = $roleAssignment->_id;
        $this->role_assignment_ids = $roleAssignmentIds;

        $roleAssignmentObjs[] = [
            '_id' => $roleAssignment->_id,
            'weight' => $roleAssignment->weight,
            'organization_id' => $roleAssignment->organization_id,
            'roles' => $roles
        ];
        $this->role_assignments = $roleAssignmentObjs;

        $isSaved = $this->save();
        $this->forgetCachedPermissions();

        return $isSaved;
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
     * Determine given data present in array or not
     *
     * @param $needle
     * @param $haystack
     * @param bool $strict
     * @return bool
     */
    public function inMultiDimensionalArray($needle, $haystack, $strict = false)
    {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) ||
                (is_array($item) && $this->inMultiDimensionalArray($needle, $item, $strict))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the model has (one of) the given role(s).
     *
     * @param $roles
     * @param $roleAssignmentId
     * @param $allRoles
     * @return bool
     */
    public function hasRole($roles, $roleAssignmentId, $allRoles): bool
    {
        if (\is_string($roles) && false !== \strpos($roles, '|')) {
            $roles = \explode('|', $roles);
        }

        if (empty($roleAssignmentId)) {
            return false;
        }

        $isRoleAvailable = false;

        foreach ($this->role_assignments as $roleAssignment) {
            if (in_array($roleAssignmentId, $roleAssignment)) {
                foreach ($roles as $role) {
                    if ($this->inMultiDimensionalArray($role, $roleAssignment['roles'])) {
                        $isRoleAvailable = true;
                    } else {
                        if ($allRoles) {
                            $isRoleAvailable = false;
                        }
                    }
                }
            }
        }

        return $isRoleAvailable;
    }

    /**
     * Determine if the model has any of the given role(s).
     *
     * @param $roles
     * @param $organization
     * @return bool
     */
    public function hasAnyRole($roles, $organization): bool
    {
        $roleAssignment = RoleAssignment::where('organization_id', $organization->_id)->first();

        return $this->hasRole($roles, $roleAssignment->_id, false);
    }

    /**
     * Determine if the model has all of the given role(s).
     *
     * @param $roles
     * @param $organization
     * @return bool
     */
    public function hasAllRoles($roles, $organization): bool
    {
        $roleAssignment = RoleAssignment::where('organization_id', $organization->_id)->first();

        return $this->hasRole($roles, $roleAssignment->_id, true);
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
