<?php

namespace Maklad\Permission\Test;

use Maklad\Permission\Exceptions\GuardDoesNotMatch;
use Maklad\Permission\Exceptions\PermissionDoesNotExist;
use Maklad\Permission\Helpers;
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
                $message = Helpers::getPermissionDoesNotExistMessage('permission-does-not-exist', 'web');
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
                $message = Helpers::getGuardDoesNotMatchMessage(collect(['web', 'api']), 'admin');
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
    }
}
