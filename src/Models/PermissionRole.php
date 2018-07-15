<?php

namespace Maklad\Permission\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Maklad\Permission\Contracts\PermissionRoleInterface;
use Maklad\Permission\Contracts\RoleInterface;
use Maklad\Permission\Exceptions\GuardDoesNotMatch;
use Maklad\Permission\Exceptions\RoleAlreadyExists;
use Maklad\Permission\Exceptions\RoleDoesNotExist;
use Maklad\Permission\Guard;
use Maklad\Permission\Helpers;
use Maklad\Permission\Traits\HasPermissions;
use Maklad\Permission\Traits\RefreshesPermissionCache;
use ReflectionException;

/**
 * Class Role
 * @package Maklad\Permission\Models
 */
class PermissionRole extends Model implements PermissionRoleInterface
{

    public $guarded = ['id'];
    protected $helpers;

    public $collection = "permission_role";

    /**
     * A model may have multiple permissions
     */
    public function permissions()
    {
        return $this->belongsTo('permission');
    }

    /**
     * A model may have multiple permissions
     */
    public function roles()
    {
        return $this->belongsTo('role');
    }
}
