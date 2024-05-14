<?php

namespace Maklad\Permission\Test;

use Maklad\Permission\Exceptions\GuardDoesNotMatch;
use Maklad\Permission\Exceptions\RoleDoesNotExist;
use Maklad\Permission\Models\Role;
use Monolog\Level;

class HasRolesTest extends TestCase
{
    /** @test */
    public function it_can_determine_that_the_user_does_not_have_a_role()
    {
        $this->assertFalse($this->testUser->hasRole('testRole'));
    }

    /** @test */
    public function it_can_assign_and_remove_a_role()
    {
        $this->testUser->assignRole($this->testUserRole);

        $this->assertTrue($this->testUser->hasRole($this->testUserRole));

        $this->testUser->removeRole($this->testUserRole);

        $this->refreshTestUser();

        $this->assertFalse($this->testUser->hasRole($this->testUserRole));

        $this->assertCount(0, $this->testUserRole->users);
    }

    /** @test */
    public function it_can_assign_a_role_using_an_object()
    {
        $this->testUser->assignRole($this->testUserRole);

        $this->assertTrue($this->testUser->hasRole($this->testUserRole));
    }

    /** @test */
    public function it_can_assign_multiple_roles_at_once()
    {
        $this->testUser->assignRole('testRole', 'testRole2');

        $this->assertTrue($this->testUser->hasRole('testRole'));

        $this->assertTrue($this->testUser->hasRole('testRole2'));
    }

    /** @test */
    public function it_can_assign_multiple_roles_using_an_array()
    {
        $this->testUser->assignRole(['testRole', 'testRole2']);

        $this->assertTrue($this->testUser->hasRole('testRole'));

        $this->assertTrue($this->testUser->hasRole('testRole2'));
    }

    /** @test */
    public function it_throws_an_exception_when_assigning_a_role_that_does_not_exist()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(RoleDoesNotExist::class);

                $this->testUser->assignRole('evil-emperor');
            } finally {
                $message = $this->helpers->getRoleDoesNotExistMessage('evil-emperor', 'web');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function it_can_only_assign_roles_from_the_correct_guard()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(RoleDoesNotExist::class);

                $this->testUser->assignRole('testAdminRole');
            } finally {
                $message = $this->helpers->getRoleDoesNotExistMessage('testAdminRole', 'web');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function it_throws_an_exception_when_assigning_a_role_from_a_different_guard()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(GuardDoesNotMatch::class);

                $this->testUser->assignRole($this->testAdminRole);
            } finally {
                $message = $this->helpers->getGuardDoesNotMatchMessage(collect(['web']), 'admin');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function it_can_sync_roles_from_a_string()
    {
        $this->testUser->assignRole('testRole');

        $this->testUser->syncRoles('testRole2');

        $this->assertFalse($this->testUser->hasRole('testRole'));

        $this->assertTrue($this->testUser->hasRole('testRole2'));
    }

    /** @test */
    public function it_can_sync_multiple_roles()
    {
        $this->testUser->syncRoles('testRole', 'testRole2');

        $this->assertTrue($this->testUser->hasRole('testRole'));

        $this->assertTrue($this->testUser->hasRole('testRole2'));
    }

    /** @test */
    public function it_can_sync_multiple_roles_from_an_array()
    {
        $this->testUser->syncRoles(['testRole', 'testRole2']);

        $this->assertTrue($this->testUser->hasRole('testRole'));

        $this->assertTrue($this->testUser->hasRole('testRole2'));
    }

    /** @test */
    public function it_will_remove_all_roles_when_an_empty_array_is_passed_to_sync_roles()
    {
        $this->testUser->assignRole('testRole');

        $this->testUser->assignRole('testRole2');

        $this->testUser->syncRoles([]);

        $this->assertFalse($this->testUser->hasRole('testRole'));

        $this->assertFalse($this->testUser->hasRole('testRole2'));
    }

    /** @test */
    public function it_throws_an_exception_when_syncing_a_role_from_another_guard()
    {
        $can_logs = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            try {
                $this->expectException(GuardDoesNotMatch::class);

                $this->testUser->syncRoles('testRole', $this->testAdminRole);
            } finally {
                $message = $this->helpers->getGuardDoesNotMatchMessage(collect(['web']), 'admin');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function it_can_scope_users_using_a_string()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $user1->assignRole('testRole');
        $user1->assignRole('testRole2');
        $user2->assignRole('testRole2');

        $scopedUsers1 = User::role('testRole')->get();
        $scopedUsers2 = User::role('testRole2')->get();

        $this->assertEquals($scopedUsers1->count(), 1);
        $this->assertEquals($scopedUsers2->count(), 2);
    }

    /** @test */
    public function it_can_scope_users_using_an_array()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $user1->assignRole($this->testUserRole);
        $user2->assignRole('testRole2');

        $scopedUsers1 = User::role([$this->testUserRole])->get();
        $scopedUsers2 = User::role(['testRole', 'testRole2'])->get();

        $this->assertEquals($scopedUsers1->count(), 1);
        $this->assertEquals($scopedUsers2->count(), 2);
    }

    /** @test */
    public function it_can_scope_users_using_a_collection()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $user1->assignRole($this->testUserRole);
        $user2->assignRole('testRole2');

        $scopedUsers1 = User::role([$this->testUserRole])->get();
        $scopedUsers2 = User::role(collect(['testRole', 'testRole2']))->get();

        $this->assertEquals($scopedUsers1->count(), 1);
        $this->assertEquals($scopedUsers2->count(), 2);
    }

    /** @test */
    public function it_can_scope_users_using_an_object()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $user1->assignRole($this->testUserRole);
        $user2->assignRole('testRole2');

        $scopedUsers1 = User::role($this->testUserRole)->get();
        $scopedUsers2 = User::role([$this->testUserRole])->get();
        $scopedUsers3 = User::role(collect([$this->testUserRole]))->get();

        $this->assertEquals($scopedUsers1->count(), 1);
        $this->assertEquals($scopedUsers2->count(), 1);
        $this->assertEquals($scopedUsers3->count(), 1);
    }

    /** @test */
    public function it_can_determine_that_a_user_has_one_of_the_given_roles()
    {
        $roleModel = app(Role::class);

        $roleModel->create(['name' => 'second role']);

        $this->assertFalse($this->testUser->hasRole($roleModel->all()));

        $this->testUser->assignRole($this->testUserRole);

        $this->refreshTestUser();

        $this->assertTrue($this->testUser->hasRole($roleModel->all()));

        $this->assertTrue($this->testUser->hasAnyRole($roleModel->all()));

        $this->assertTrue($this->testUser->hasAnyRole('testRole'));

        $this->assertFalse($this->testUser->hasAnyRole('role does not exist'));

        $this->assertTrue($this->testUser->hasAnyRole(['testRole']));

        $this->assertTrue($this->testUser->hasAnyRole(['testRole', 'role does not exist']));

        $this->assertFalse($this->testUser->hasAnyRole(['role does not exist']));
    }

    /** @test */
    public function it_can_determine_that_a_user_has_all_of_the_given_roles()
    {
        $roleModel = app(Role::class);

        $this->assertFalse($this->testUser->hasAllRoles($roleModel->first()));

        $this->assertFalse($this->testUser->hasAllRoles('testRole'));

        $this->assertFalse($this->testUser->hasAllRoles($roleModel->all()));

        $roleModel->create(['name' => 'second role']);

        $this->testUser->assignRole($this->testUserRole);

        $this->refreshTestUser();

        $this->assertFalse($this->testUser->hasAllRoles(['testRole', 'second role']));

        $this->testUser->assignRole('second role');

        $this->refreshTestUser();

        $this->assertTrue($this->testUser->hasAllRoles(['testRole', 'second role']));
    }

    /** @test */
    public function it_can_determine_that_a_user_does_not_have_a_role_from_another_guard()
    {
        $this->assertFalse($this->testUser->hasRole('testAdminRole'));

        $this->assertFalse($this->testUser->hasRole($this->testAdminRole));

        $this->testUser->assignRole('testRole');

        $this->refreshTestUser();

        $this->assertTrue($this->testUser->hasAnyRole(['testRole', 'testAdminRole']));

        $this->assertFalse($this->testUser->hasAnyRole('testAdminRole', $this->testAdminRole));
    }

    /** @test */
    public function it_can_retrieve_role_names()
    {
        $this->testUser->assignRole('testRole', 'testRole2');

        $this->assertEquals(
            collect(['testRole', 'testRole2']),
            $this->testUser->getRoleNames()
        );
    }

    /** @test */
    public function it_does_not_detach_roles_when_soft_deleting()
    {
        $user = SoftDeletingUser::create(['email' => 'test@example.com']);
        $user->assignRole('testRole');
        $user->delete();
        $user = SoftDeletingUser::withTrashed()->find($user->id);
        $this->assertTrue($user->hasRole('testRole'));
    }

    /** @test */
    public function it_can_give_and_revoke_multiple_roles()
    {
        $this->testUser->assignRole('testRole');
        $this->testUser->assignRole('testRole2');

        $this->testUser->removeRole('testRole', 'testRole2');

        $this->assertFalse($this->testUser->hasRole('testRole'));

        $this->assertFalse($this->testUser->hasRole('testRole2'));
    }

    /** @test */
    public function a_model_that_uses_hasRoles_trait_should_not_have_users_method()
    {
        $this->assertFalse(method_exists($this->testUser, 'users'));
    }
}
