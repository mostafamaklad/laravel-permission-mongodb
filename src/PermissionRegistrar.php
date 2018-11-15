<?php

namespace Maklad\Permission;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;
use Maklad\Permission\Contracts\PermissionInterface as Permission;

/**
 * Class PermissionRegistrar
 * @package Maklad\Permission
 */
class PermissionRegistrar
{
    /** @var \Illuminate\Contracts\Auth\Access\Gate */
    protected $gate;

    /** @var \Illuminate\Contracts\Cache\Repository */
    protected $cache;

    /** @var string */
    protected $cacheKey = 'maklad.permission.cache';

    /** @var string */
    protected $permissionClass;

    /** @var string */
    protected $roleClass;

    /**
     * PermissionRegistrar constructor.
     * @param Gate $gate
     * @param Repository $cache
     */
    public function __construct(Gate $gate, Repository $cache)
    {
        $this->gate = $gate;
        $this->cache = $cache;
        $this->permissionClass = config('permission.models.permission');
        $this->roleClass = config('permission.models.role');
    }

    /**
     * Register Permissions
     *
     * @return bool
     */
    public function registerPermissions(): bool
    {
        $this->getPermissions()->map(function (Permission $permission) {
            $this->gate->define($permission->name, function (Authorizable $user) use ($permission) {
                return $user->hasPermissionTo($permission) ?: null;
            });
        });

        return true;
    }

    /**
     * Forget cached permission
     */
    public function forgetCachedPermissions()
    {
        $this->cache->forget($this->cacheKey);
    }

    /**
     * Get Permissions
     *
     * @return Collection
     */
    public function getPermissions(): Collection
    {
        return $this->cache->remember($this->cacheKey, config('permission.cache_expiration_time'), function () {
            return $this->getPermissionClass()->with('roles')->get();
        });
    }

    /**
     * Get Permission class
     *
     * @return \Illuminate\Foundation\Application|mixed
     */
    public function getPermissionClass()
    {
        return app($this->permissionClass);
    }

    /**
     * Get Role class
     *
     * @return \Illuminate\Foundation\Application|mixed
     */
    public function getRoleClass()
    {
        return app($this->roleClass);
    }
}
