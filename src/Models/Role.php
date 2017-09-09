<?php
declare(strict_types=1);

namespace Maklad\Permission\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Relations\BelongsToMany;
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
        $this->helpers            = new Helpers();
        $attributes['guard_name'] = $attributes['guard_name'] ?? $this->helpers->config('auth.defaults.guard');

        parent::__construct($attributes);

        $this->setTable($this->helpers->config('permission.table_names.roles'));
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
        $helpers = new Helpers();

        $attributes['guard_name'] = $attributes['guard_name'] ?? $helpers->config('auth.defaults.guard');

        if (static::where('name', $attributes['name'])->where('guard_name', $attributes['guard_name'])->first()) {
            $name      = $attributes['name'];
            $guardName = $attributes['guard_name'];
            throw new RoleAlreadyExists($helpers->getRoleAlreadyExistsMessage($name, $guardName));
        }

        return static::query()->create($attributes);
    }

    /**
     * A role may be given various permissions.
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            $this->helpers->config('permission.models.permission'),
            $this->helpers->config('permission.table_names.role_has_permissions')
        );
    }

    /**
     * A role belongs to some users of the model associated with its guard.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany($this->helpers->getModelForGuard($this->attributes['guard_name']));
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
        $helpers   = new Helpers();
        $guardName = $guardName ?? $helpers->config('auth.defaults.guard');

        $role = static::where('name', $name)->where('guard_name', $guardName)->first();

        if (! $role) {
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
            $permission = $this->helpers->app(Permission::class)->findByName($permission, $this->getDefaultGuardName());
        }

        if (! $this->getGuardNames()->contains($permission->guard_name)) {
            $expected = $this->getGuardNames();
            $given    = $permission->guard_name;

            throw new GuardDoesNotMatch($this->helpers->getGuardDoesNotMatchMessage($expected, $given));
        }

        return $this->permissions->contains('id', $permission->id);
    }
}
