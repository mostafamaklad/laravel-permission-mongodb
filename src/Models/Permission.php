<?php

namespace Maklad\Permission\Models;

use Illuminate\Support\Collection;
use Maklad\Permission\Contracts\PermissionInterface;
use Maklad\Permission\Exceptions\PermissionAlreadyExists;
use Maklad\Permission\Exceptions\PermissionDoesNotExist;
use Maklad\Permission\Guard;
use Maklad\Permission\Helpers;
use Maklad\Permission\PermissionRegistrar;
use Maklad\Permission\Traits\HasRoles;
use Maklad\Permission\Traits\RefreshesPermissionCache;
use MongoDB\Laravel\Eloquent\Model;
use ReflectionException;
use function app;

/**
 * Class Permission
 * @property string $_id
 * @package Maklad\Permission\Models
 */
class Permission extends Model implements PermissionInterface
{
    use HasRoles;
    use RefreshesPermissionCache;

    public $guarded = ['id'];
    protected Helpers $helpers;

    /**
     * Permission constructor.
     *
     * @param array $attributes
     *
     * @throws ReflectionException
     */
    public function __construct(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? (new Guard())->getDefaultName(static::class);

        parent::__construct($attributes);

        $this->helpers = new Helpers();

        $this->setTable(config('permission.collection_names.permissions'));
    }

    /**
     * Create new Permission
     *
     * @param array $attributes
     *
     * @return $this|\Illuminate\Database\Eloquent\Model
     * @throws PermissionAlreadyExists
     * @throws ReflectionException
     */
    public static function create(array $attributes = []): \Illuminate\Database\Eloquent\Model|static
    {
        $helpers = new Helpers();
        $attributes['guard_name'] = $attributes['guard_name'] ?? (new Guard())->getDefaultName(static::class);

        if (static::getPermissions()->where('name', $attributes['name'])->where(
            'guard_name',
            $attributes['guard_name']
        )->first()) {
            $name = (string)$attributes['name'];
            $guardName = (string)$attributes['guard_name'];
            throw new PermissionAlreadyExists($helpers->getPermissionAlreadyExistsMessage($name, $guardName));
        }

        return static::query()->create($attributes);
    }

    /**
     * Find or create permission by its name (and optionally guardName).
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @return PermissionInterface
     * @throws ReflectionException
     */
    public static function findOrCreate(string $name, string $guardName = null): PermissionInterface
    {
        $guardName = $guardName ?? (new Guard())->getDefaultName(static::class);

        $permission = static::getPermissions()->filter(function ($permission) use ($name, $guardName) {
            return $permission->name === $name && $permission->guard_name === $guardName;
        })->first();

        if (!$permission) {
            $permission = static::create(['name' => $name, 'guard_name' => $guardName]);
        }

        return $permission;
    }

    /**
     * A permission can be applied to roles.
     * @return mixed
     */
    public function rolesQuery(): mixed
    {
        $roleClass = $this->getRoleClass();
        return $roleClass->query()->where('permission_ids', 'all', [$this->_id]);
    }

    /**
     * A permission can be applied to roles.
     * @return mixed
     */
    public function getRolesAttribute(): mixed
    {
        return $this->rolesQuery()->get();
    }

    /**
     * A permission belongs to some users of the model associated with its guard.
     * @return mixed
     */
    public function usersQuery(): mixed
    {
        $usersClass = app($this->helpers->getModelForGuard($this->attributes['guard_name']));
        return $usersClass->query()->where('permission_ids', 'all', [$this->_id]);
    }

    /**
     * A permission belongs to some users of the model associated with its guard.
     * @return mixed
     */
    public function getUsersAttribute(): mixed
    {
        return $this->usersQuery()->get();
    }

    /**
     * Find a permission by its name (and optionally guardName).
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @return PermissionInterface
     * @throws ReflectionException
     */
    public static function findByName(string $name, string $guardName = null): PermissionInterface
    {
        $guardName = $guardName ?? (new Guard())->getDefaultName(static::class);

        $permission = static::getPermissions()->filter(function ($permission) use ($name, $guardName) {
            return $permission->name === $name && $permission->guard_name === $guardName;
        })->first();

        if (!$permission) {
            $helpers = new Helpers();
            throw new PermissionDoesNotExist($helpers->getPermissionDoesNotExistMessage($name, $guardName));
        }

        return $permission;
    }

    /**
     * Get the current cached permissions.
     * @return Collection
     */
    protected static function getPermissions(): Collection
    {
        return app(PermissionRegistrar::class)->getPermissions();
    }
}
