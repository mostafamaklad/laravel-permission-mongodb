<?php
namespace Maklad\Permission\Models;

use Illuminate\Support\Collection;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Relations\BelongsToMany;
use Maklad\Permission\Contracts\PermissionInterface;
use Maklad\Permission\Exceptions\PermissionAlreadyExists;
use Maklad\Permission\Exceptions\PermissionDoesNotExist;
use Maklad\Permission\Helpers;
use Maklad\Permission\PermissionRegistrar;
use Maklad\Permission\Traits\RefreshesPermissionCache;

/**
 * Class Permission
 * @package Maklad\Permission\Models
 */
class Permission extends Model implements PermissionInterface
{
    use RefreshesPermissionCache;

    public $guarded = ['id'];
    protected $helpers;

    /**
     * Permission constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? config('auth.defaults.guard');

        parent::__construct($attributes);

        $this->helpers = new Helpers();

        $this->setTable(config('permission.collection_names.permissions'));
    }

    /**
     * Create new Permission
     *
     * @param array $attributes
     *
     * @return $this|Model
     * @throws PermissionAlreadyExists
     */
    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? config('auth.defaults.guard');

        if (static::getPermissions()->where('name', $attributes['name'])->where(
            'guard_name',
            $attributes['guard_name']
        )->first()) {
            $name = $attributes['name'];
            $guardName = $attributes['guard_name'];
            $helpers = new Helpers();
            throw new PermissionAlreadyExists($helpers->getPermissionAlreadyExistsMessage($name, $guardName));
        }

        if (app()::VERSION < '5.4') {
            return parent::create($attributes);
        }

        return static::query()->create($attributes);
    }

    /**
     * A permission can be applied to roles.
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        $roleModel = config('permission.models.role');

        return $this->belongsToMany($roleModel);
    }

    /**
     * A permission belongs to some users of the model associated with its guard.
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        $model = $this->helpers->getModelForGuard($this->attributes['guard_name']);

        return $this->belongsToMany($model);
    }

    /**
     * Find a permission by its name (and optionally guardName).
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @return PermissionInterface
     * @throws PermissionDoesNotExist
     */
    public static function findByName(string $name, $guardName = null): PermissionInterface
    {
        $guardName = $guardName ?? config('auth.defaults.guard');

        $permission = static::getPermissions()->where('name', $name)->where('guard_name', $guardName)->first();

        if (! $permission) {
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
