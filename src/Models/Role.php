<?php
declare(strict_types=1);

namespace Maklad\Permission\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Maklad\Permission\Contracts\RoleInterface;
use Maklad\Permission\Exceptions\GuardDoesNotMatch;
use Maklad\Permission\Exceptions\RoleAlreadyExists;
use Maklad\Permission\Exceptions\RoleDoesNotExist;
use Maklad\Permission\Helpers;
use Maklad\Permission\Traits\HasPermissions;
use Maklad\Permission\Traits\RefreshesPermissionCache;

/**
 * Class Role
 * @package Maklad\Permission\Models
 */
class Role extends Model implements RoleInterface
{
    use HasPermissions;
    use RefreshesPermissionCache;

    public $guarded = ['id'];
    protected $helpers;

    /**
     * Role constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? config('auth.defaults.guard');

        parent::__construct($attributes);

        $this->helpers = new Helpers();

        $this->setTable(config('permission.collection_names.roles'));
    }

    /**
     * @param array $attributes
     *
     * @return $this|Model
     * @throws RoleAlreadyExists
     * @internal param array $attributes§
     *
     */
    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? \config('auth.defaults.guard');
        $helpers                  = new Helpers();

        if (static::where('name', $attributes['name'])->where('guard_name', $attributes['guard_name'])->first()) {
            $name      = (string) $attributes['name'];
            $guardName = (string) $attributes['guard_name'];
            throw new RoleAlreadyExists($helpers->getRoleAlreadyExistsMessage($name, $guardName));
        }

        if ($helpers->isNotLumen() && app()::VERSION < '5.4') {
            return parent::create($attributes);
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
     * @throws \Maklad\Permission\Exceptions\RoleAlreadyExists
     */
    public static function findOrCreate(string $name, $guardName = null): RoleInterface
    {
        $guardName = $guardName ?? config('auth.defaults.guard');

        $role = static::where('name', $name)
                      ->where('guard_name', $guardName)
                      ->first();

        if (! $role) {
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
     */
    public static function findByName(string $name, $guardName = null): RoleInterface
    {
        $guardName = $guardName ?? config('auth.defaults.guard');

        $role = static::where('name', $name)
                      ->where('guard_name', $guardName)
                      ->first();

        if (! $role) {
            $helpers = new Helpers();
            throw new RoleDoesNotExist($helpers->getRoleDoesNotExistMessage($name, $guardName));
        }

        return $role;
    }

    /**
     * Determine if the user may perform the given permission.
     *
     * @param string|Permission $permission
     *
     * @return bool
     *
     * @throws GuardDoesNotMatch
     */
    public function hasPermissionTo($permission): bool
    {
        if (\is_string($permission)) {
            $permission = app(Permission::class)->findByName($permission, $this->getDefaultGuardName());
        }

        if (! $this->getGuardNames()->contains($permission->guard_name)) {
            $expected = $this->getGuardNames();
            $given    = $permission->guard_name;

            throw new GuardDoesNotMatch($this->helpers->getGuardDoesNotMatchMessage($expected, $given));
        }

        return $this->permissions->contains('id', $permission->id);
    }
}
