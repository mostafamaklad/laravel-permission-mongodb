<?php

namespace Maklad\Permission\Traits;

use Jenssegers\Mongodb\Eloquent\Model;
use Maklad\Permission\PermissionRegistrar;

trait RefreshesPermissionCache
{
    public static function bootRefreshesPermissionCache()
    {
        static::saved(function (Model $model) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        static::deleted(function (Model $model) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }
}
