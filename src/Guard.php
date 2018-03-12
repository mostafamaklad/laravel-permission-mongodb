<?php
/**
 * Created by PhpStorm.
 * User: mostafamaklad
 * Date: 12/03/2018
 * Time: 2:23 PM
 */

namespace Maklad\Permission;

use Illuminate\Support\Collection;

/**
 * Class Guard
 * @package Maklad\Permission
 */
class Guard
{
    /**
     * return collection of (guard_name) property if exist on class or object
     * otherwise will return collection of guards names that exists in config/auth.php.
     *
     * @param $model
     *
     * @return Collection
     * @throws \ReflectionException
     */
    public static function getNames($model) : Collection
    {
        if (\is_object($model)) {
            $guardName = $model->guard_name ?? null;
        }

        if (! isset($guardName)) {
            $class = \is_object($model) ? \get_class($model) : $model;
            $guardName = (new \ReflectionClass($class))->getDefaultProperties()['guard_name'] ?? null;
        }

        if ($guardName) {
            return collect($guardName);
        }
        return collect(config('auth.guards'))
            ->map(function ($guard) {
                return config("auth.providers.{$guard['provider']}.model");
            })
            ->filter(function ($model) use ($class) {
                return $class === $model;
            })
            ->keys();
    }

    /**
     * Return Default Guard name
     *
     * @param $class
     *
     * @return string
     * @throws \ReflectionException
     */
    public static function getDefaultName($class): string
    {
        $default = config('auth.defaults.guard');
        return static::getNames($class)->first() ?: $default;
    }
}
