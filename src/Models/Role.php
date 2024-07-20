<?php

namespace Maklad\Permission\Models;

use Illuminate\Database\Eloquent\Builder;
use Maklad\Permission\Contracts\PermissionInterface;
use Maklad\Permission\Contracts\RoleInterface;
use Maklad\Permission\Exceptions\GuardDoesNotMatch;
use Maklad\Permission\Exceptions\RoleAlreadyExists;
use Maklad\Permission\Exceptions\RoleDoesNotExist;
use Maklad\Permission\Guard;
use Maklad\Permission\Helpers;
use Maklad\Permission\Traits\HasPermissions;
use Maklad\Permission\Traits\RefreshesPermissionCache;
use MongoDB\Laravel\Eloquent\Model;
use ReflectionException;
use function is_string;

/**
 * Class Role
 * @property string $_id
 * @package Maklad\Permission\Models
 */
class Role extends Model implements RoleInterface
{
    use HasPermissions;
    use RefreshesPermissionCache;

    public $guarded = ['id'];
    protected Helpers $helpers;

    /**
     * Role constructor.
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

        $this->setTable(config('permission.collection_names.roles'));
    }

    /**
     * @param array $attributes
     *
     * @return Builder|\Illuminate\Database\Eloquent\Model|RoleInterface
     *
     * @throws RoleAlreadyExists
     * @throws ReflectionException
     */
    public static function create(array $attributes = []): \Illuminate\Database\Eloquent\Model|RoleInterface|Builder
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? (new Guard())->getDefaultName(static::class);
        $helpers = new Helpers();

        if (static::query()->where('name', $attributes['name'])->where('guard_name', $attributes['guard_name'])->first()) {
            $name = (string)$attributes['name'];
            $guardName = (string)$attributes['guard_name'];
            throw new RoleAlreadyExists($helpers->getRoleAlreadyExistsMessage($name, $guardName));
        }

        return static::query()->create($attributes);
    }

    /**
     * Find or create role by its name (and optionally guardName).
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @return RoleInterface
     * @throws RoleAlreadyExists
     * @throws ReflectionException
     */
    public static function findOrCreate(string $name, string $guardName = null): Role
    {
        $guardName = $guardName ?? (new Guard())->getDefaultName(static::class);

        $role = static::query()
            ->where('name', $name)
            ->where('guard_name', $guardName)
            ->first();

        if (!$role) {
            $role = static::create(['name' => $name, 'guard_name' => $guardName]);
        }

        return $role;
    }

    /**
     * Find a role by its name and guard name.
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @return RoleInterface
     * @throws RoleDoesNotExist
     * @throws ReflectionException
     */
    public static function findByName(string $name, string $guardName = null): RoleInterface
    {
        $guardName = $guardName ?? (new Guard())->getDefaultName(static::class);

        $role = static::query()
            ->where('name', $name)
            ->where('guard_name', $guardName)
            ->first();

        if (!$role) {
            $helpers = new Helpers();
            throw new RoleDoesNotExist($helpers->getRoleDoesNotExistMessage($name, $guardName));
        }

        return $role;
    }

    /**
     * A permission belongs to some users of the model associated with its guard.
     * @return mixed
     */
    public function usersQuery(): mixed
    {
        $usersClass = app($this->helpers->getModelForGuard($this->attributes['guard_name']));
        return $usersClass->query()->where('role_ids', 'all', [$this->_id]);
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
     * Determine if the user may perform the given permission.
     *
     * @param string|Permission $permission
     *
     * @return bool
     *
     * @throws GuardDoesNotMatch
     * @throws ReflectionException
     */
    public function hasPermissionTo(string|PermissionInterface $permission): bool
    {
        if (is_string($permission)) {
            $permission = $this->getPermissionClass()->findByName($permission, $this->getDefaultGuardName());
        }

        if (!$this->getGuardNames()->contains($permission->guard_name)) {
            $expected = $this->getGuardNames();
            $given = $permission->guard_name;

            throw new GuardDoesNotMatch($this->helpers->getGuardDoesNotMatchMessage($expected, $given));
        }

        return in_array($permission->_id, $this->permission_ids ?? [], true);
    }
}
