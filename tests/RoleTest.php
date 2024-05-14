<?php

namespace Maklad\Permission\Test;

use Maklad\Permission\Exceptions\GuardDoesNotMatch;
use Maklad\Permission\Exceptions\PermissionDoesNotExist;
use Maklad\Permission\Exceptions\RoleAlreadyExists;
use Maklad\Permission\Models\Permission;
use Maklad\Permission\Models\Role;
use Monolog\Level;

class RoleTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'other-permission']);

        Permission::create(['name' => 'wrong-guard-permission', 'guard_name' => 'admin']);
    }

    /** @test */
    public function it_has_user_models_of_the_right_class()
    {
        $this->testAdmin->assignRole($this->testAdminRole);

        $this->testUser->assignRole($this->testUserRole);

        $this->assertCount(1, $this->testUserRole->users);
        $this->assertEquals(get_class($this->testUser), get_class($this->testUserRole->users->first()));
        $this->assertInstanceOf(User::class, $this->testUserRole->users->first());

        $this->testUser->delete();
        $this->assertEquals(0, $this->testUserRole->usersQuery()->count());
    }

    /** @test */
    public function it_throws_an_exception_when_the_role_already_exists()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(RoleAlreadyExists::class);

                \app(\config('permission.models.role'))->create(['name' => 'test-role']);
                \app(\config('permission.models.role'))->create(['name' => 'test-role']);
            } finally {
                $message = $this->helpers->getRoleAlreadyExistsMessage('test-role', 'web');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function it_can_be_given_a_permission()
    {
        $this->testUserRole->givePermissionTo('edit-articles');

        $this->assertTrue($this->testUserRole->hasPermissionTo('edit-articles'));
    }

    /** @test */
    public function it_throws_an_exception_when_given_a_permission_that_does_not_exist()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(PermissionDoesNotExist::class);

                $this->testUserRole->givePermissionTo('create-evil-empire');
            } finally {
                $message = $this->helpers->getPermissionDoesNotExistMessage('create-evil-empire', 'web');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function it_throws_an_exception_when_given_a_permission_that_belongs_to_another_guard()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(GuardDoesNotMatch::class);

                $this->testUserRole->givePermissionTo($this->testAdminPermission);
            } finally {
                $message = $this->helpers->getGuardDoesNotMatchMessage(collect(['web']), 'admin');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function it_can_be_given_multiple_permissions_using_an_array()
    {
        $this->testUserRole->givePermissionTo(['edit-articles', 'edit-news']);

        $this->assertTrue($this->testUserRole->hasPermissionTo('edit-articles'));
        $this->assertTrue($this->testUserRole->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function it_can_be_given_multiple_permissions_using_multiple_arguments()
    {
        $this->testUserRole->givePermissionTo('edit-articles', 'edit-news');

        $this->assertTrue($this->testUserRole->hasPermissionTo('edit-articles'));
        $this->assertTrue($this->testUserRole->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function it_can_sync_permissions()
    {
        $this->testUserRole->givePermissionTo('edit-articles');

        $this->testUserRole->syncPermissions('edit-news');

        $this->assertFalse($this->testUserRole->hasPermissionTo('edit-articles'));

        $this->assertTrue($this->testUserRole->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function it_throws_an_exception_when_syncing_permissions_that_do_not_exist()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            try {
                $this->testUserRole->givePermissionTo('edit-articles');

                $this->expectException(PermissionDoesNotExist::class);

                $this->testUserRole->syncPermissions('permission-does-not-exist');
            } finally {
                $message = $this->helpers->getPermissionDoesNotExistMessage('permission-does-not-exist', 'web');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function it_throws_an_exception_when_syncing_permissions_that_belong_to_a_different_guard()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(GuardDoesNotMatch::class);

                $this->testUserRole->syncPermissions($this->testAdminPermission);
            } finally {
                $message = $this->helpers->getGuardDoesNotMatchMessage(collect(['web']), 'admin');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function it_will_remove_all_permissions_when_passing_an_empty_array_to_sync_permissions()
    {
        $this->testUserRole->givePermissionTo('edit-articles');

        $this->testUserRole->givePermissionTo('edit-news');

        $this->testUserRole->syncPermissions([]);

        $this->assertFalse($this->testUserRole->hasPermissionTo('edit-articles'));

        $this->assertFalse($this->testUserRole->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function it_can_revoked_a_permission()
    {
        $this->testUserRole->givePermissionTo('edit-articles');

        $this->assertTrue($this->testUserRole->hasPermissionTo('edit-articles'));

        $this->testUserRole->revokePermissionTo('edit-articles');

        $this->testUserRole = $this->testUserRole->fresh();

        $this->assertFalse($this->testUserRole->hasPermissionTo('edit-articles'));
    }

    /** @test */
    public function it_can_be_given_a_permission_using_objects()
    {
        $this->testUserRole->givePermissionTo($this->testUserPermission);

        $this->assertTrue($this->testUserRole->hasPermissionTo($this->testUserPermission));
    }

    /** @test */
    public function it_returns_false_if_it_does_not_have_the_permission()
    {
        $this->assertFalse($this->testUserRole->hasPermissionTo('other-permission'));
    }

    /** @test */
    public function it_throws_an_exception_if_the_permission_does_not_exist()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(PermissionDoesNotExist::class);

                $this->testUserRole->hasPermissionTo('doesnt-exist');
            } finally {
                $message = $this->helpers->getPermissionDoesNotExistMessage('doesnt-exist', 'web');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function it_returns_false_if_it_does_not_have_a_permission_object()
    {
        $permission = app(Permission::class)->findByName('other-permission');

        $this->assertFalse($this->testUserRole->hasPermissionTo($permission));
    }

    /** @test */
    public function it_throws_an_exception_when_a_permission_of_the_wrong_guard_is_passed_in()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(GuardDoesNotMatch::class);

                $permission = app(Permission::class)->findByName('wrong-guard-permission', 'admin');

                $this->testUserRole->hasPermissionTo($permission);
            } finally {
                $message = $this->helpers->getGuardDoesNotMatchMessage(collect(['web']), 'admin');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function it_belongs_to_a_guard()
    {
        $role = \app(\config('permission.models.role'))->create(['name' => 'admin', 'guard_name' => 'admin']);

        $this->assertEquals('admin', $role->guard_name);
    }

    /** @test */
    public function it_belongs_to_the_default_guard_by_default()
    {
        $this->assertEquals($this->app['config']->get('auth.defaults.guard'), $this->testUserRole->guard_name);
    }

    /** @test */
    public function it_creates_role_object_with_findOrCreate_if_it_does_not_have_a_role_object()
    {
        $role = app(Role::class)->findOrCreate('another-role');

        $this->assertFalse($this->testUser->hasRole($role));

        $this->testUser->assignRole($role);

        $this->testUser = $this->testUser->fresh();

        $this->assertTrue($this->testUser->hasRole('another-role'));
    }
}
