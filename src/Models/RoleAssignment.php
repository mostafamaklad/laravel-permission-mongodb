<?php
namespace Maklad\Permission\Models;

use Jenssegers\Mongodb\Eloquent\Model;


class RoleAssignment extends Model
{
    /**
     * RoleAssignment constructor.
     *
     * @param array $attributes
     *
     * @throws \ReflectionException
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(\config('permission.collection_names.role_assignments'));
    }
}
