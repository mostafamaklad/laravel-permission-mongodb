<?php
declare(strict_types=1);

namespace Maklad\Permission\Traits;

use Jenssegers\Mongodb\Eloquent\Model;
use Maklad\Permission\Helpers;
use Maklad\Permission\PermissionRegistrar;

/**
 * Trait RefreshesPermissionCache
 * @package Maklad\Permission\Traits
 */
trait RefreshesPermissionCache
{
    public static function bootRefreshesPermissionCache()
    {
        $helpers = new Helpers();
        static::saved(function () use ($helpers) {
            $helpers->app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        static::deleted(function () use ($helpers) {
            $helpers->app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }
}
