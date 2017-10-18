<?php
declare(strict_types=1);

namespace Maklad\Permission\Commands;

use Illuminate\Console\Command;
use Maklad\Permission\Contracts\PermissionInterface as Permission;
use Maklad\Permission\Contracts\RoleInterface as Role;

/**
 * Class CreateRole
 * @package Maklad\Permission\Commands
 */
class CreateRole extends Command
{
    protected $signature = 'permission:create-role
        {name : The name of the role}
        {guard? : The name of the guard}
        {--permission=* : The name of the permission}';

    protected $description = 'Create a role';

    public function handle()
    {
        $role_class       = \app(Role::class);

        $name        = $this->argument('name');
        $guard       = $this->argument('guard');
        $permissions = $this->option('permission');

        $role = $role_class::create([
            'name'       => $name,
            'guard_name' => $guard
        ]);

        $this->info("Role `{$role->name}` created");

        $role->givePermissionTo($permissions);
        $permissions_str = $role->permissions->implode('name', '`, `');
        $this->info("Permissions `{$permissions_str}` has been given to role `{$role->name}`");
    }
}
