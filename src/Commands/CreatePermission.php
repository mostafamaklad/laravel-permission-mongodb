<?php

namespace Maklad\Permission\Commands;

use Illuminate\Console\Command;
use Maklad\Permission\Contracts\PermissionInterface as Permission;

/**
 * Class CreatePermission
 * @package Maklad\Permission\Commands
 */
class CreatePermission extends Command
{
    protected $signature = 'permission:create-permission 
                {name : The name of the permission} 
                {guard? : The name of the guard}';

    protected $description = 'Create a permission';

    public function handle()
    {
        $permissionClass = \app(\config('permission.models.permission'));

        $permission = $permissionClass::create([
            'name'       => $this->argument('name'),
            'guard_name' => $this->argument('guard')
        ]);

        $this->info("Permission `{$permission->name}` created");
    }
}
