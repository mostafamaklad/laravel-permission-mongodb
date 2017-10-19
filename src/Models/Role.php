<?php
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
        $attributes['guard_name'] = $attributes['guard_name'] ?? config('auth.defaults.guard');

        if (static::where('name', $attributes['name'])->where('guard_name', $attributes['guard_name'])->first()) {
            $name = $attributes['name'];
            $guardName = $attributes['guard_name'];
            $helpers = new Helpers();
            throw new RoleAlreadyExists($helpers->getRoleAlreadyExistsMessage($name, $guardName));
        }

        if (app()::VERSION < '5.4') {
            return parent::create($attributes);
        }

        return static::query()->create($attributes);
    }

    /**
     * A role may be given various permissions.
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        $permissionModel = config('permission.models.permission');

        return $this->belongsToMany($permissionModel);
    }

    /**
     * A role belongs to some users of the model associated with its guard.
     */
    public function users(): BelongsToMany
    {
        $model = $this->helpers->getModelForGuard($this->attributes['guard_name']);

        return $this->belongsToMany($model);
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

        $role = static::where('name', $name)->where('guard_name', $guardName)->first();

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
        if (is_string($permission)) {
            $permission = app(Permission::class)->findByName($permission, $this->getDefaultGuardName());
        }

        if (! $this->getGuardNames()->contains($permission->guard_name)) {
            $expected = $this->getGuardNames();
            $given = $permission->guard_name;

            throw new GuardDoesNotMatch($this->helpers->getGuardDoesNotMatchMessage($expected, $given));
        }

        return $this->permissions->contains('id', $permission->id);
    }
}
