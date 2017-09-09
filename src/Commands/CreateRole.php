<?php
declare(strict_types=1);

namespace Maklad\Permission\Commands;

use Illuminate\Console\Command;
use Maklad\Permission\Contracts\RoleInterface as Role;

/**
 * Class CreateRole
 * @package Maklad\Permission\Commands
 */
class CreateRole extends Command
{
    protected $signature = 'permission:create-role
        {name : The name of the role}
        {guard? : The name of the guard}';

    protected $description = 'Create a role';

    public function handle()
    {
        $roleClass = \app(Role::class);

        $role = $roleClass::create([
            'name'       => $this->argument('name'),
            'guard_name' => $this->argument('guard')
        ]);

        $this->info("Role `{$role->name}` created");
    }
}
