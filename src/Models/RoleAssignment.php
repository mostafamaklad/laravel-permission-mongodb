<?php
namespace Maklad\Permission\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Maklad\Permission\Traits\HasRoles;


class RoleAssignment extends Model
{
    use HasRoles;

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

    protected $fillable = [
        'organization_id',
        'weight',
        'role_ids'
    ];
}
