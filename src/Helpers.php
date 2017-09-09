<?php
declare(strict_types=1);

namespace Maklad\Permission;

use Illuminate\Container\Container;
use Illuminate\Support\Collection;

/**
 * Class Helpers
 * @package Maklad\Permission
 */
class Helpers
{
    /**
     * @param string $guard
     *
     * @return string|null
     */
    public function getModelForGuard(string $guard)
    {
        $guards = new Collection($this->config('auth.guards'));
        return $guards->map(function ($guard) {
            return $this->config("auth.providers.{$guard['provider']}.model");
        })->get($guard);
    }

    /**
     * @param Collection $expected
     * @param string $given
     *
     * @return string
     */
    public function getGuardDoesNotMatchMessage(Collection $expected, string $given): string
    {
        return "The given role or permission should use guard `{$expected->implode(', ')}` instead of `{$given}`.";
    }

    /**
     * @param string $name
     * @param string $guardName
     *
     * @return string
     */
    public function getPermissionAlreadyExistsMessage(string $name, string $guardName): string
    {
        return "A permission `{$name}` already exists for guard `{$guardName}`.";
    }

    /**
     * @param string $name
     * @param string $guardName
     *
     * @return string
     */
    public function getPermissionDoesNotExistMessage(string $name, string $guardName): string
    {
        return "There is no permission named `{$name}` for guard `{$guardName}`.";
    }

    /**
     * @param string $name
     * @param string $guardName
     *
     * @return string
     */
    public function getRoleAlreadyExistsMessage(string $name, string $guardName): string
    {
        return "A role `{$name}` already exists for guard `{$guardName}`.";
    }

    /**
     * @param string $name
     *
     * @param string $guardName
     *
     * @return string
     */
    public function getRoleDoesNotExistMessage(string $name, string $guardName): string
    {
        return "There is no role named `{$name}` for guard `{$guardName}`.";
    }

    /**
     * @param null|string|array $key
     * @param null $default
     *
     * @return mixed|static
     */
    public function config($key = null, $default = null)
    {
        if (null === $key) {
            return $this->app('config');
        }

        if (\is_array($key)) {
            return $this->app('config')->set($key);
        }

        return $this->app('config')->get($key, $default);
    }

    /**
     * @param null|string|array $abstract
     * @param array $parameters
     *
     * @return mixed|static
     */
    public function app($abstract = null, array $parameters = [])
    {
        if (null === $abstract) {
            return Container::getInstance();
        }

        return empty($parameters)
            ? Container::getInstance()->make($abstract)
            : Container::getInstance()->makeWith($abstract, $parameters);
    }

    /**
     * @param $code
     * @param string $message
     * @param array $headers
     */
    public function abort($code, $message = '', array $headers = [])
    {
        $this->app()->abort($code, $message, $headers);
    }
}
