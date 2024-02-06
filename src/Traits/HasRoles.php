<?php

namespace Maklad\Permission\Traits;

use Illuminate\Support\Collection;
use MongoDB\Laravel\Eloquent\Builder;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsToMany;
use Maklad\Permission\Contracts\RoleInterface as Role;
use Maklad\Permission\Helpers;
use Maklad\Permission\PermissionRegistrar;
use ReflectionException;
use function collect;

/**
 * Trait HasRoles
 * @package Maklad\Permission\Traits
 */
trait HasRoles
{
    use HasPermissions;

    private $roleClass;

    public static function bootHasRoles(): void
    {
        static::deleting(function (Model $model) {
            if (isset($model->forceDeleting) && !$model->forceDeleting) {
                return;
            }

            $model->roles()->sync([]);
        });
    }

    public function getRoleClass()
    {
        if ($this->roleClass === null) {
            $this->roleClass = app(PermissionRegistrar::class)->getRoleClass();
        }
        return $this->roleClass;
    }

    /**
     * A model may have multiple roles.
     */
    public function roles(): BelongsToMany|\Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(config('permission.models.role'));
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
     * Assign the given role to the model.
     *
     * @param array|string|Role ...$roles
     *
     * @return array|Role|string
     * @throws ReflectionException
     */
    public function assignRole(...$roles)
    {
        $roles = collect($roles)
            ->flatten()
            ->map(function ($role) {
                return $this->getStoredRole($role);
            })
            ->each(function ($role) {
                $this->ensureModelSharesGuard($role);
            })
            ->all();

        $this->roles()->saveMany($roles);

        $this->forgetCachedPermissions();

        return $roles;
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
        collect($roles)
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
     * @return array|Role|string
     * @throws ReflectionException
     */
    public function syncRoles(...$roles): Role|array|string
    {
        $this->roles()->sync([]);

        return $this->assignRole($roles);
    }

    /**
     * Determine if the model has (one of) the given role(s).
     *
     * @param string|array|Role|Collection $roles
     *
     * @return bool
     */
    public function hasRole($roles): bool
    {
        if (\is_string($roles) && str_contains($roles, '|')) {
            $roles = \explode('|', $roles);
        }

        if (\is_string($roles) || $roles instanceof Role) {
            return $this->roles->contains('name', $roles->name ?? $roles);
        }

        $roles = collect()->make($roles)->map(function ($role) {
            return $role instanceof Role ? $role->name : $role;
        });

        return !$roles->intersect($this->roles->pluck('name'))->isEmpty();
    }

    /**
     * Determine if the model has any of the given role(s).
     *
     * @param string|array|Role|Collection $roles
     *
     * @return bool
     */
    public function hasAnyRole($roles): bool
    {
        return $this->hasRole($roles);
    }

    /**
     * Determine if the model has all the given role(s).
     *
     * @param $roles
     *
     * @return bool
     */
    public function hasAllRoles(...$roles): bool
    {
        $helpers = new Helpers();
        $roles = $helpers->flattenArray($roles);

        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }
        return true;
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
            return $this->getRoleClass()->findByName($role, $this->getDefaultGuardName());
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

        return $roles->map(function ($role) {
            return $this->getStoredRole($role);
        });
    }
}
