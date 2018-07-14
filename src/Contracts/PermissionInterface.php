<?php

namespace Maklad\Permission\Contracts;

use Maklad\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Interface PermissionInterface
 * @package Maklad\Permission\Contracts
 */
interface PermissionInterface
{
    /**
     * A permission can be applied to roles.
     * @return BelongsToMany
     */
    public function roles();

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
