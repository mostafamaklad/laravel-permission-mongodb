<?php
declare(strict_types=1);

namespace Maklad\Permission;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;
use Jenssegers\Mongodb\Eloquent\Model;
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

    protected $helpers;

    public function __construct(Gate $gate, Repository $cache)
    {
        $this->gate   = $gate;
        $this->cache  = $cache;
        $this->helpers = new Helpers();
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

    /**
     * Get permissions
     *
     * @return Collection
     */
    public function getPermissions(): Collection
    {
        $expirationTime = $this->helpers->config('permission.cache_expiration_time');
        return $this->cache->remember($this->cacheKey, $expirationTime, function () {
            return $this->helpers->app(Permission::class)->with('roles')->get();
        });
    }
}
