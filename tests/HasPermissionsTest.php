<?php

namespace Maklad\Permission\Test;

use Maklad\Permission\Exceptions\GuardDoesNotMatch;
use Maklad\Permission\Exceptions\PermissionDoesNotExist;
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
            $this->app['config']->set('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(PermissionDoesNotExist::class);

                $this->testUser->givePermissionTo('permission-does-not-exist');
            } finally {
                $message = $this->helpers->getPermissionDoesNotExistMessage('permission-does-not-exist', 'web');
                $this->assertLogMessage($message, Logger::ALERT);
            }
        }
    }

    /** @test */
    public function it_throws_an_exception_when_assigning_a_permission_to_a_user_from_a_different_guard()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            $this->app['config']->set('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(GuardDoesNotMatch::class);

                $this->testUser->givePermissionTo($this->testAdminPermission);
            } finally {
                $message = $this->helpers->getGuardDoesNotMatchMessage(collect(['web', 'api']), 'admin');
                $this->assertLogMessage($message, Logger::ALERT);
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
        $this->assertEquals($scopedUsers1->count(), 1);
        $this->assertEquals($scopedUsers2->count(), 1);
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
        $this->assertEquals(2, $this->testUserRole->permissions()->count());
        $this->testUserRole->revokePermissionTo(['edit-articles', 'edit-news']);
        $this->assertEquals(0, $this->testUserRole->permissions()->count());
    }
}
