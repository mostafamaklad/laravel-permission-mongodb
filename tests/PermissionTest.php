<?php

namespace Maklad\Permission\Test;

use Maklad\Permission\Exceptions\PermissionAlreadyExists;
use Maklad\Permission\Models\Permission;
use Monolog\Level;

class PermissionTest extends TestCase
{
    /** @test */
    public function it_throws_an_exception_when_the_permission_already_exists()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(PermissionAlreadyExists::class);

                \app(\config('permission.models.permission'))->create(['name' => 'test-permission']);
                \app(\config('permission.models.permission'))->create(['name' => 'test-permission']);
            } finally {
                $message = $this->helpers->getPermissionAlreadyExistsMessage('test-permission', 'web');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function it_belongs_to_a_guard()
    {
        $permission = \app(\config('permission.models.permission'))->create(['name' => 'can-edit', 'guard_name' => 'admin']);

        $this->assertEquals('admin', $permission->guard_name);
    }

    /** @test */
    public function it_belongs_to_the_default_guard_by_default()
    {
        $this->assertEquals($this->app['config']->get('auth.defaults.guard'), $this->testUserPermission->guard_name);
    }

    /** @test */
    public function it_has_user_models_of_the_right_class()
    {
        $this->testAdmin->givePermissionTo($this->testAdminPermission);

        $this->testUser->givePermissionTo($this->testUserPermission);

        $this->assertCount(1, $this->testUserPermission->users);
        $this->assertEquals(get_class($this->testUser), get_class($this->testUserPermission->users->first()));
        $this->assertInstanceOf(User::class, $this->testUserPermission->users->first());

        $this->testUser->delete();
        $this->assertEquals(0, $this->testUserPermission->usersQuery()->count());
    }

    /** @test */
    public function it_creates_permission_object_with_findOrCreate_if_it_does_not_have_a_permission_object()
    {
        $permission = app(Permission::class)->findOrCreate('another-permission');
        $this->assertFalse($this->testUserRole->hasPermissionTo($permission));

        $this->testUserRole->givePermissionTo($permission);

        $this->testUserRole = $this->testUserRole->fresh();
        $this->assertTrue($this->testUserRole->hasPermissionTo('another-permission'));
    }
}
