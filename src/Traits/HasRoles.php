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

        $roleAssignment = \app(RoleAssignment::class)->where('organization_id', $this->_id)->first();

        if (empty($roleAssignment)) {
            $roleAssignment = \app(RoleAssignment::class)->create([
                'organization_id' => $this->_id,
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
     * @param $organization
     * @param mixed ...$roles
     * @return array|bool
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

        if (empty($roleAssignment)
            || count(array_intersect($roleIds, $roleAssignment->role_ids)) != count($roles)) {
            return false;
        }

        if ((!empty($this->role_assignment_ids) && in_array($roleAssignment->_id, $this->role_assignment_ids))) {
            return $this->updateAssignOrgRole($roles, $roleAssignment);
        }

        $roleAssignmentIds = [];
        $roleAssignmentObjs = [];
        if (!empty($this->role_assignment_ids) && !empty($this->role_assignments)) {
            $roleAssignmentIds = $this->role_assignment_ids;
            $roleAssignmentObjs = $this->role_assignments;
        }

        $roleAssignmentIds[] = $roleAssignment->_id;
        $this->role_assignment_ids = $roleAssignmentIds;

        $preparedRoleAssignment = [
            '_id' => $roleAssignment->_id,
            'weight' => $roleAssignment->weight,
            'organization_id' => $roleAssignment->organization_id,
            'roles' => $roles
        ];

        $roleAssignmentObjs[] = $preparedRoleAssignment;
        $this->role_assignments = $roleAssignmentObjs;

        $isSaved = $this->save();
        $this->forgetCachedPermissions();

        if($isSaved){
            return $preparedRoleAssignment;
        }

        return false;
    }

    /**
     * Update assign organizational role to user
     *
     * @param $roles
     * @param $roleAssignment
     * @return bool
     */
    function updateAssignOrgRole($roles, $roleAssignment)
    {
        $userRoles = [];
        $userRoleAssignments = [];
        $targetRoleAssignment = [];

        $userRoleAssignments = $this->role_assignments;

        $roleIds = array_column($roles, '_id');

        foreach ($userRoleAssignments as $key => $userRoleAssignment) {
            if (in_array($roleAssignment->_id, $userRoleAssignment)) {

                $userRoleIds = array_column($userRoleAssignment['roles'], '_id');
                if (count(array_intersect($roleIds, $userRoleIds)) > 0) {
                    return false;
                }

                $targetRoleAssignment = $userRoleAssignment;
                $userRoles = array_merge($userRoleAssignment['roles'], $roles);
                break;
            }
        }

        $targetRoleAssignment['roles'] = $userRoles;

        foreach ($userRoleAssignments as $key => $value) {
            if ($value['_id'] == $targetRoleAssignment['_id']) {
                $userRoleAssignments[$key] = $targetRoleAssignment;
            }
        }

        $this->role_assignments = $userRoleAssignments;
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
     * @deprecated
     * Remove all current roles and set the given ones.
     *
     * @param array ...$roles
     *
     * @return $this
     */
    public function syncRoles(...$roles)
    {
//        $roleAssignment = RoleAssignment::where('organization_id', $this->_id)->first();
//
//        if (empty($roleAssignment)) {
//            return [];
//        }
//
//        $roleAssignment->roles()->sync([]);
//
//        return $this->assignRole($roles);
        return [];
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

        $roleArray = [];

        if (empty($roleAssignmentId)) {
            foreach ($this->role_assignments as $roleAssignment) {
                $roleArray[] = array_column($roleAssignment['roles'], 'name');
            }
        } else {
            foreach ($this->role_assignments as $roleAssignment) {
                if (in_array($roleAssignmentId, $roleAssignment)) {
                    $roleArray[] = array_column($roleAssignment['roles'], 'name');
                    break;
                }
            }
        }

        $roleArray = collect($roleArray)->flatten()->toArray();

        if ($allRoles) {
            return (count(array_intersect($roles, $roleArray)) == count($roles));
        }
        return (count(array_intersect($roles, $roleArray)) > 0);
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
        $roleAssignmentID = null;

        if (!empty($organization)) {
            $roleAssignment = RoleAssignment::where('organization_id', $organization->_id)->first();
            $roleAssignmentID = $organization->_id;
        }

        return $this->hasRole($roles, $roleAssignmentID, false);
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
        $roleAssignmentID = null;

        if (!empty($organization)) {
            $roleAssignment = RoleAssignment::where('organization_id', $organization->_id)->first();
            $roleAssignmentID = $roleAssignment->_id;
        }

        return $this->hasRole($roles, $roleAssignmentID, true);
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

    /**
     * Check permission (e.g. Read PR, Read PO etc.) for given user
     *
     * @param null $permission
     * @return array
     */
    public function getOrganizationsPermission($permission = null)
    {
        $organizations = Organization::all();

        $organizationArray = [];
        foreach ($organizations as $organization) {
            foreach ($this->role_assignments as $roleAssignment) {
                $permissionName = [];
                if ($organization->weight == $roleAssignment['weight']) {
                    if(!empty($permission)){
                        foreach ($roleAssignment['roles'] as $roles){
                            $names = array_column($roles['permissions'], 'name');
                            $permissionName = array_merge($permissionName, $names);
                        }
                        if(in_array($permission, $permissionName)){
                            $organizationArray[$organization->name][] = $roleAssignment['organization_id'];
                        }
                    }
                    else{
                        $organizationArray[$organization->name][] = $roleAssignment['organization_id'];
                    }
                }

            }
        }

        return $organizationArray;
    }

    /**
     * Update user's role assignment with given role assignments payload
     *
     * @param $givenRoleAssignments
     * @return mixed
     * @throws \Exception
     */
    public function updateUserRoleAssignments($givenRoleAssignments)
    {
        if (empty($givenRoleAssignments)) {
            return false;
        }

        $userRoleAssignments = $this->role_assignments;
        $userOrganizationIds = array_column($userRoleAssignments, 'organization_id');

        foreach ($givenRoleAssignments as $key => $givenRoleAssignment) {
            if (!RoleAssignment::where('organization_id', $givenRoleAssignment['organization_id'])->exists()) {
                throw new \Exception('Role Assignment is not found.');
            } else {
                if (!in_array($givenRoleAssignment['organization_id'], $userOrganizationIds) && !$givenRoleAssignment['is_deleted']) {
                    if (!$this->checkRolesInRoleAssignment($givenRoleAssignment['roles'], $givenRoleAssignment['organization_id'])) {
                        throw new \Exception('Roles does not belongs to given organization');
                    }

                    $savedRoleAssignment = $this->assignOrgRole($givenRoleAssignment['organization_id'], $givenRoleAssignment['roles']);

                    if (!is_array($savedRoleAssignment)) {
                        throw new \Exception('Something went wrong.');
                    }
                    $userRoleAssignments[] = $savedRoleAssignment;
                } else {
                    $userRoleAssignments = $this->prepareUserRoleAssignment($userRoleAssignments, $givenRoleAssignment);
                }
            }
        }

        $this->role_assignments = $userRoleAssignments;

        $newRoleAssignmentIds = array_column($userRoleAssignments, '_id');
        $this->role_assignment_ids = $newRoleAssignmentIds;

        return $this->save();
    }

    /**
     * Prepared (edit and delete) user role assignments with given parameters
     *
     * @param $userRoleAssignments
     * @param $givenRoleAssignment
     * @return array
     * @throws \Exception
     */
    public function prepareUserRoleAssignment($userRoleAssignments, $givenRoleAssignment)
    {
        foreach ($userRoleAssignments as $index => $roleAssignment) {
            if (!$this->checkRolesInRoleAssignment($givenRoleAssignment['roles'], $givenRoleAssignment['organization_id'])) {
                throw new \Exception('Roles does not belongs to given organization');
            }

            if ($givenRoleAssignment['organization_id'] == $roleAssignment['organization_id']) {
                if (!$givenRoleAssignment['is_deleted']) {
                    $roles = \collect($givenRoleAssignment['roles'])
                        ->flatten()
                        ->map(function ($role) {
                            $role = $this->getStoredRole($role);

                            return $this->prepareRoles($role);
                        })
                        ->toArray($givenRoleAssignment['roles']);

                    $userRoleAssignments[$index]['roles'] = $roles;
                } else {
                    unset($userRoleAssignments[$index]);
                }
            }
        }

        return $userRoleAssignments;
    }

    /**
     * Check given roles belongs to given organization
     *
     * @param $roles
     * @param $organizationId
     * @return bool
     */
    public function checkRolesInRoleAssignment($roles, $organizationId)
    {
        $roles = \collect($roles)
            ->flatten()
            ->map(function ($role) {
                return $this->getStoredRole($role);
            })
            ->toArray($roles);
        $roleIds = array_column($roles, '_id');

        $roleAssignmentCount = RoleAssignment::where('organization_id', $organizationId)
            ->whereIn('role_ids', $roleIds)
            ->count();

        return $roleAssignmentCount > 0;
    }
}
