<?php

namespace Maklad\Permission\Test;

use Artisan;
use Maklad\Permission\Models\Permission;
use Maklad\Permission\Models\Role;

class CommandTest extends TestCase
{
    /** @test */
    public function it_can_create_a_role()
    {
        Artisan::call('permission:create-role', ['name' => 'new-role']);

        $this->assertCount(1, Role::where('name', 'new-role')->get());
    }

    /** @test */
    public function it_can_create_a_role_with_a_specific_guard()
    {
        Artisan::call('permission:create-role', [
            'name' => 'new-role',
            'guard' => 'api',
        ]);

        $this->assertCount(1, Role::where('name', 'new-role')
            ->where('guard_name', 'api')
            ->get());
    }

    /** @test */
    public function it_can_create_a_permission()
    {
        Artisan::call('permission:create-permission', ['name' => 'new-permission']);

        $this->assertCount(1, Permission::where('name', 'new-permission')->get());
    }

    /** @test */
    public function it_can_create_a_permission_with_a_specific_guard()
    {
        Artisan::call('permission:create-permission', [
            'name' => 'new-permission',
            'guard' => 'api',
        ]);

        $this->assertCount(1, Permission::where('name', 'new-permission')
            ->where('guard_name', 'api')
            ->get());
    }

    /** @test */
    public function it_can_create_a_role_and_assign_permissions()
    {
        Artisan::call('permission:create-permission', ['name' => 'test1',]);
        Artisan::call('permission:create-permission', ['name' => 'test2',]);
        Artisan::call('permission:create-role', [
            'name'         => 'new-role',
            '--permission' => ['test1', 'test2']
        ]);

        $role = Role::findByName('new-role');
        $this->assertCount(2, $role->permissions);
    }

    /** @test */
    public function it_can_create_a_role_with_guard_and_assign_permissions()
    {
        Artisan::call('permission:create-permission', [
            'name'  => 'test1',
            'guard' => 'api',
        ]);
        Artisan::call('permission:create-permission', [
            'name'  => 'test2',
            'guard' => 'api',
        ]);
        Artisan::call('permission:create-role', [
            'name'         => 'new-role',
            'guard'        => 'api',
            '--permission' => ['test1', 'test2']
        ]);

        $role = Role::findByName('new-role', 'api');
        $this->assertCount(2, $role->permissions);
    }
}
