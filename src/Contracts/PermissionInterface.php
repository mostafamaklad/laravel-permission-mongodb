<?php

namespace Maklad\Permission\Contracts;

use Jenssegers\Mongodb\Relations\BelongsToMany;
use Maklad\Permission\Exceptions\PermissionDoesNotExist;

interface PermissionInterface
{
    /**
     * A permission can be applied to roles.
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany;

    /**
     * Find a permission by its name.
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @throws PermissionDoesNotExist
     *
     * @return PermissionInterface
     */
    public static function findByName(string $name, $guardName): PermissionInterface;
}
