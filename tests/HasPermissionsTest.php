<?php

namespace Maklad\Permission\Test;

use Monolog\Level;
use Maklad\Permission\Exceptions\GuardDoesNotMatch;
use Maklad\Permission\Exceptions\PermissionDoesNotExist;
use Maklad\Permission\Models\Permission;
use Maklad\Permission\Models\Role;
use Monolog\Logger;

class HasPermissionsTest extends TestCase
{
    /** @test */
    public function it_can_assign_a_permission_to_a_user()
    {
        $this->testUser->givePermissionTo($this->testUserPermission);

        $this->refreshTestUser();

        $this->assertTrue($this->testUser->hasPermissionTo($this->testUserPermission));
    }

    /** @test */
    public function it_throws_an_exception_when_assigning_a_permission_that_does_not_exist()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(PermissionDoesNotExist::class);

                $this->testUser->givePermissionTo('permission-does-not-exist');
            } finally {
                $message = $this->helpers->getPermissionDoesNotExistMessage('permission-does-not-exist', 'web');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function it_throws_an_exception_when_assigning_a_permission_to_a_user_from_a_different_guard()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(GuardDoesNotMatch::class);

                $this->testUser->givePermissionTo($this->testAdminPermission);
            } finally {
                $message = $this->helpers->getGuardDoesNotMatchMessage(collect(['web']), 'admin');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function it_can_revoke_a_permission_from_a_user()
    {
        $this->testUser->givePermissionTo($this->testUserPermission);

        $this->refreshTestUser();

        $this->assertTrue($this->testUser->hasPermissionTo($this->testUserPermission));

        $this->testUser->revokePermissionTo($this->testUserPermission);

        $this->refreshTestUser();

        $this->assertFalse($this->testUser->hasPermissionTo($this->testUserPermission));

        $this->assertCount(0, $this->testUserPermission->users);
    }

    /** @test */
    public function it_can_scope_users_using_a_string()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $user1->givePermissionTo(['edit-articles', 'edit-news']);
        // giving user2 permission throgth the role
        $this->testUserRole->givePermissionTo('edit-articles');
        $user2->assignRole('testRole');
        $scopedUsers1 = User::permission('edit-articles')->get();
        $scopedUsers2 = User::permission(['edit-news'])->get();

        $this->assertEquals($scopedUsers1->count(), 2);
        $this->assertEquals($scopedUsers2->count(), 1);
    }

    /** @test */
    public function it_can_scope_users_using_an_array()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $user1->givePermissionTo(['edit-articles', 'edit-news']);
        // giving user2 permission throgth the role
        $this->testUserRole->givePermissionTo('edit-articles');
        $user2->assignRole('testRole');
        $scopedUsers1 = User::permission(['edit-articles', 'edit-news'])->get();
        $scopedUsers2 = User::permission(['edit-news'])->get();
        $this->assertEquals($scopedUsers1->count(), 2);
        $this->assertEquals($scopedUsers2->count(), 1);
    }

    /** @test */
    public function it_can_scope_users_using_a_collection()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $user1->givePermissionTo(['edit-articles', 'edit-news']);
        // giving user2 permission throgth the role
        $this->testUserRole->givePermissionTo('edit-articles');
        $user2->assignRole('testRole');
        $scopedUsers1 = User::permission(collect(['edit-articles', 'edit-news']))->get();
        $scopedUsers2 = User::permission(collect(['edit-news']))->get();

        $this->assertEquals($scopedUsers1->count(), 2);
        $this->assertEquals($scopedUsers2->count(), 1);
    }

    /** @test */
    public function it_can_scope_users_using_an_object()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user1->givePermissionTo($this->testUserPermission->name);
        $scopedUsers1 = User::permission($this->testUserPermission)->get();
        $scopedUsers2 = User::permission([$this->testUserPermission])->get();
        $scopedUsers3 = User::permission(collect([$this->testUserPermission]))->get();
        $this->assertEquals($scopedUsers1->count(), 1);
        $this->assertEquals($scopedUsers2->count(), 1);
        $this->assertEquals($scopedUsers3->count(), 1);
    }

    /** @test */
    public function it_can_scope_users_without_permissions_only_role()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $this->testUserRole->givePermissionTo('edit-articles');
        $user1->assignRole('testRole');
        $user2->assignRole('testRole');
        $scopedUsers = User::permission('edit-articles')->get();
        $this->assertEquals($scopedUsers->count(), 2);
    }

    /** @test */
    public function it_can_scope_users_without_permissions_only_permission()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $user1->givePermissionTo(['edit-news']);
        $user2->givePermissionTo(['edit-articles', 'edit-news']);
        $scopedUsers = User::permission('edit-news')->get();
        $this->assertEquals($scopedUsers->count(), 2);
    }

    /** @test */
    public function it_throws_an_exception_when_trying_to_scope_a_permission_from_another_guard()
    {
        $this->expectException(PermissionDoesNotExist::class);
        User::permission('testAdminPermission')->get();
        $this->expectException(GuardDoesNotMatch::class);
        User::permission($this->testAdminPermission)->get();
    }

    /** @test */
    public function it_doesnt_detach_permissions_when_soft_deleting()
    {
        $user = SoftDeletingUser::create(['email' => 'test@example.com']);
        $user->givePermissionTo(['edit-news']);
        $user->delete();
        $user = SoftDeletingUser::withTrashed()->find($user->id);
        $this->assertTrue($user->hasPermissionTo('edit-news'));
    }

    /** @test */
    public function it_can_give_and_revoke_multiple_permissions()
    {
        $this->testUserRole->givePermissionTo(['edit-articles', 'edit-news']);
        $this->assertEquals(2, $this->testUserRole->permissionsQuery()->count());
        $this->testUserRole->revokePermissionTo(['edit-articles', 'edit-news']);
        $this->assertEquals(0, $this->testUserRole->permissionsQuery()->count());
    }

    /** @test */
    public function it_can_retrieve_permission_names()
    {
        $this->testUser->givePermissionTo('edit-articles', 'edit-news');

        $this->assertEquals(
            collect(['edit-articles', 'edit-news']),
            $this->testUser->getPermissionNames()
        );
    }

    /** @test */
    public function it_can_determine_that_the_user_has_any_of_the_permissions_directly_using_an_array()
    {
        $this->assertFalse($this->testUser->hasAnyPermission(['edit-articles']));

        $this->testUser->givePermissionTo('edit-articles');

        $this->refreshTestUser();

        $this->assertTrue($this->testUser->hasAnyPermission(['edit-news', 'edit-articles']));

        $this->testUser->givePermissionTo('edit-news');

        $this->refreshTestUser();

        $this->testUser->revokePermissionTo($this->testUserPermission);

        $this->assertTrue($this->testUser->hasAnyPermission(['edit-articles', 'edit-news']));
    }

    /** @test */
    public function it_can_determine_that_the_user_has_any_of_the_permissions_via_role()
    {
        $this->testUserRole->givePermissionTo('edit-articles');

        $this->testUser->assignRole('testRole');

        $this->assertTrue($this->testUser->hasAnyPermission('edit-news', 'edit-articles'));
    }

    /** @test */
    public function it_can_determine_that_user_has_direct_permission()
    {
        $this->testUser->givePermissionTo('edit-articles');
        $this->testUser->assignRole('testRole');
        $this->testUserRole->givePermissionTo('edit-news');
        $this->refreshTestUser();
        $this->assertFalse($this->testUser->hasDirectPermission('edit-news'));
        $this->assertTrue($this->testUser->hasDirectPermission('edit-articles'));
        $this->assertEquals(
            collect(['edit-articles']),
            $this->testUser->getDirectPermissions()->pluck('name')
        );

        $this->testUser->revokePermissionTo('edit-articles');
        $this->refreshTestUser();
        $this->assertFalse($this->testUser->hasDirectPermission('edit-articles'));
    }

    /** @test */
    public function it_can_list_all_the_permissions_via_his_roles()
    {
        $roleModel = app(Role::class);
        $roleModel->findByName('testRole2')->givePermissionTo('edit-news');

        $this->testUserRole->givePermissionTo('edit-articles');
        $this->testUser->assignRole('testRole', 'testRole2');

        $this->testUser->givePermissionTo('edit-categories');

        $this->assertEquals(
            collect(['edit-articles', 'edit-news']),
            $this->testUser->getPermissionsViaRoles()->pluck('name')
        );
    }

    /** @test */
    public function it_can_list_all_the_coupled_permissions_both_directly_and_via_roles()
    {
        $this->testUser->givePermissionTo('edit-news');

        $this->testUserRole->givePermissionTo('edit-articles');
        $this->testUser->assignRole('testRole');

        $this->assertEquals(
            collect(['edit-articles', 'edit-news']),
            $this->testUser->getAllPermissions()->pluck('name')
        );
    }

    /** @test */
    public function it_can_work_with_a_user_that_does_not_have_any_permissions_at_all()
    {
        // TODO try to use object without saving it to database
        $user = User::create(['email' => 'new@user.com']);

        $this->assertFalse($user->hasPermissionTo('edit-articles'));
    }

    /** @test */
    public function it_can_determine_that_the_user_has_any_of_the_permissions_directly()
    {
        $this->assertFalse($this->testUser->hasAnyPermission('edit-articles'));

        $this->testUser->givePermissionTo('edit-articles');

        $this->refreshTestUser();

        $this->assertTrue($this->testUser->hasAnyPermission('edit-news', 'edit-articles'));

        $this->testUser->givePermissionTo('edit-news');

        $this->refreshTestUser();

        $this->testUser->revokePermissionTo($this->testUserPermission);

        $this->assertTrue($this->testUser->hasAnyPermission('edit-articles', 'edit-news'));
    }

    /** @test */
    public function it_can_determine_that_the_user_does_not_have_a_permission()
    {
        $this->assertFalse($this->testUser->hasPermissionTo('edit-articles'));
    }

    /** @test */
    public function it_throws_an_exception_when_the_permission_does_not_exist()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(PermissionDoesNotExist::class);

                $this->testUser->hasPermissionTo('does-not-exist');
            } finally {
                $message = $this->helpers->getPermissionDoesNotExistMessage('does-not-exist', 'web');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function it_throws_an_exception_when_the_permission_does_not_exist_for_this_guard()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(PermissionDoesNotExist::class);

                $this->testUser->hasPermissionTo('admin-permission');
            } finally {
                $message = $this->helpers->getPermissionDoesNotExistMessage('admin-permission', 'web');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function it_can_assign_and_remove_a_role_on_a_permission()
    {
        $this->testUserPermission->assignRole('testRole');

        $this->assertTrue($this->testUserPermission->hasRole('testRole'));

        $this->testUserPermission->removeRole('testRole');

        $this->refreshTestUserPermission();

        $this->assertFalse($this->testUserPermission->hasRole('testRole'));
    }

    /** @test */
    public function it_can_sync_roles_from_a_string_on_a_permission()
    {
        $this->testUserPermission->assignRole('testRole');

        $this->testUserPermission->syncRoles('testRole2');

        $this->assertFalse($this->testUserPermission->hasRole('testRole'));

        $this->assertTrue($this->testUserPermission->hasRole('testRole2'));
    }

    /** @test
     * @throws \ReflectionException
     */
    public function it_can_determine_that_a_user_has_all_of_the_given_roles()
    {
        $permissionModel = app(Permission::class);

        $this->assertFalse($this->testUser->hasAllPermissions('edit-news'));

        $this->assertFalse($this->testUser->hasAllPermissions($permissionModel->all()->all()));

        $this->testUser->givePermissionTo('edit-articles');

        $this->refreshTestUser();

        $this->assertFalse($this->testUser->hasAllPermissions(['edit-news', 'edit-articles']));

        $this->testUser->givePermissionTo('edit-news');

        $this->refreshTestUser();

        $this->assertTrue($this->testUser->hasAllPermissions('edit-news', 'edit-articles'));
    }

    /** @test */
    public function it_can_determine_that_the_user_has_all_of_the_permissions_directly()
    {
        $this->testUser->givePermissionTo('edit-articles', 'edit-news');
        $this->refreshTestUser();
        $this->assertTrue($this->testUser->hasAllPermissions('edit-articles', 'edit-news'));
        $this->testUser->revokePermissionTo('edit-articles');
        $this->refreshTestUser();
        $this->assertFalse($this->testUser->hasAllPermissions('edit-articles', 'edit-news'));
    }
    /** @test */
    public function it_can_determine_that_the_user_has_all_of_the_permissions_directly_using_an_array()
    {
        $this->assertFalse($this->testUser->hasAllPermissions(['edit-articles', 'edit-news']));
        $this->testUser->revokePermissionTo('edit-articles');
        $this->refreshTestUser();
        $this->assertFalse($this->testUser->hasAllPermissions(['edit-news', 'edit-articles']));
        $this->testUser->givePermissionTo('edit-news');
        $this->refreshTestUser();
        $this->testUser->revokePermissionTo($this->testUserPermission);
        $this->assertFalse($this->testUser->hasAllPermissions(['edit-articles', 'edit-news']));
    }
    /** @test */
    public function it_can_determine_that_the_user_has_all_of_the_permissions_via_role()
    {
        $this->testUserRole->givePermissionTo('edit-articles', 'edit-news');
        $this->testUser->assignRole('testRole');
        $this->assertTrue($this->testUser->hasAllPermissions('edit-articles', 'edit-news'));
    }

    /** @test */
    public function a_model_that_uses_hasPermissions_trait_should_not_have_users_method()
    {
        $this->assertFalse(method_exists($this->testUser, 'users'));
    }
}
