<?php

namespace Maklad\Permission\Test;

use Illuminate\Database\Eloquent\SoftDeletes;

class SoftDeletingUser extends User
{
    use SoftDeletes;

    protected string $guard_name = 'web';
}
