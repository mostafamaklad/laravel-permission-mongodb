<?php

namespace Maklad\Permission\Models;


use Jenssegers\Mongodb\Eloquent\Model;

/**
 * Class Organization
 *
 * @package Maklad\Permission\Models
 * @property string $name   Name
 * @property string $class  Class
 * @property int    $weight Weight
 */
class Organization extends Model
{
    /**
     * Organization constructor.
     *
     * @param array $attributes
     *
     * @throws \ReflectionException
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(\config('permission.collection_names.organizations'));
    }

    protected $fillable = [
        'name',
        'class',
        'weight'
    ];
}
