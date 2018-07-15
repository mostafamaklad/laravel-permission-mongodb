<?php

namespace Maklad\Permission\Contracts;

/**
 * Interface PermissionRoleInterface
 * @package Maklad\Permission\Contracts
 */
interface PermissionRoleInterface
{
    /**
     * A model may have multiple permissions
     * @return belongsTo
     */
    public function permissions();

    /**
     * A model may have multiple permissions
     * @return belongsTo
     */
    public function roles();
}