<?php

namespace Maklad\Permission;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;
use Jenssegers\Mongodb\Eloquent\Model;
use Maklad\Permission\Contracts\PermissionInterface as Permission;

class PermissionRegistrar
{
    /** @var \Illuminate\Contracts\Auth\Access\Gate */
    protected $gate;

    /** @var \Illuminate\Contracts\Cache\Repository */
    protected $cache;

    /** @var string */
    protected $cacheKey = 'maklad.permission.cache';

    public function __construct(Gate $gate, Repository $cache)
    {
        $this->gate   = $gate;
        $this->cache  = $cache;
    }

    public function registerPermissions(): bool
    {
        $this->getPermissions()->map(function (Permission $permission) {
            $this->gate->define($permission->name, function (Model $user) use ($permission) {
                return $user->hasPermissionTo($permission);
            });
        });

        return true;
    }

    public function forgetCachedPermissions()
    {
        $this->cache->forget($this->cacheKey);
    }

    public function getPermissions(): Collection
    {
        return $this->cache->remember($this->cacheKey, config('permission.cache_expiration_time'), function () {
            return app(Permission::class)->with('roles')->get();
        });
    }
}
