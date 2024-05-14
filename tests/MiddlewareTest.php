<?php

namespace Maklad\Permission\Test;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Maklad\Permission\Exceptions\UnauthorizedException;
use Maklad\Permission\Middlewares\PermissionMiddleware;
use Maklad\Permission\Middlewares\RoleMiddleware;
use Monolog\Level;

class MiddlewareTest extends TestCase
{
    protected RoleMiddleware $roleMiddleware;
    protected PermissionMiddleware $permissionMiddleware;

    public function setUp(): void
    {
        parent::setUp();

        $this->roleMiddleware = new RoleMiddleware($this->app);

        $this->permissionMiddleware = new PermissionMiddleware($this->app);
    }

    /** @test */
    public function a_guest_cannot_access_a_route_protected_by_the_role_middleware()
    {
        $can_logs = [false, true];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            $this->assertEquals(
                $this->runMiddleware(
                    $this->roleMiddleware,
                    'testRole'
                ),
                403
            );

            $message = $this->helpers->getUserNotLoggedINMessage();
            $this->assertLogMessage($message, Level::Alert);
        }
    }

    /** @test */
    public function a_user_can_access_a_route_protected_by_role_middleware_if_have_this_role()
    {
        Auth::login($this->testUser);

        $this->testUser->assignRole('testRole');

        $this->assertEquals(
            $this->runMiddleware(
                $this->roleMiddleware,
                'testRole'
            ),
            200
        );
    }

    /** @test */
    public function a_user_can_access_a_route_protected_by_this_role_middleware_if_have_one_of_the_roles()
    {
        Auth::login($this->testUser);

        $this->testUser->assignRole('testRole');

        $this->assertEquals(
            $this->runMiddleware(
                $this->roleMiddleware,
                'testRole|testRole2'
            ),
            200
        );

        $this->assertEquals(
            $this->runMiddleware(
                $this->roleMiddleware,
                ['testRole2', 'testRole']
            ),
            200
        );
    }

    /** @test */
    public function a_user_cannot_access_a_route_protected_by_the_role_middleware_if_have_a_different_role()
    {
        Auth::login($this->testUser);

        $this->testUser->assignRole(['testRole']);

        $can_logs = [false, true];
        $show_permissions = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);
            $this->assertEquals(
                $this->runMiddleware(
                    $this->roleMiddleware,
                    'testRole2'
                ),
                403
            );

            foreach ($show_permissions as $show_permission) {
                config('permission.display_permission_in_exception', $show_permission);
                $message = $this->helpers->getUnauthorizedRoleMessage('testRole2');
                $this->assertShowPermission($message, 'testRole2');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function a_user_cannot_access_a_route_protected_by_role_middleware_if_have_not_roles()
    {
        Auth::login($this->testUser);

        $can_logs = [false, true];
        $show_permissions = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            $this->assertEquals(
                $this->runMiddleware(
                    $this->roleMiddleware,
                    'testRole|testRole2'
                ),
                403
            );

            foreach ($show_permissions as $show_permission) {
                config('permission.display_permission_in_exception', $show_permission);
                $message = $this->helpers->getUnauthorizedRoleMessage('testRole, testRole2');
                $this->assertShowPermission($message, 'testRole');
                $this->assertShowPermission($message, 'testRole2');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function a_user_cannot_access_a_route_protected_by_role_middleware_if_role_is_undefined()
    {
        Auth::login($this->testUser);

        $can_logs = [false, true];
        $show_permissions = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            $this->assertEquals(
                $this->runMiddleware(
                    $this->roleMiddleware,
                    ''
                ),
                403
            );

            foreach ($show_permissions as $show_permission) {
                config('permission.display_permission_in_exception', $show_permission);
                $message = $this->helpers->getUnauthorizedRoleMessage('test');
                $this->assertShowPermission($message, 'test');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function a_guest_cannot_access_a_route_protected_by_the_permission_middleware()
    {
        $can_logs = [false, true];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            $this->assertEquals(
                $this->runMiddleware(
                    $this->permissionMiddleware,
                    'edit-articles'
                ),
                403
            );

            $message = $this->helpers->getUserNotLoggedINMessage();
            $this->assertLogMessage($message, Level::Alert);
        }
    }

    /** @test */
    public function a_user_can_access_a_route_protected_by_permission_middleware_if_have_this_permission()
    {
        Auth::login($this->testUser);

        $this->testUser->givePermissionTo('edit-articles');

        $this->assertEquals(
            $this->runMiddleware(
                $this->permissionMiddleware,
                'edit-articles'
            ),
            200
        );
    }

    /** @test */
    public function a_user_can_access_a_route_protected_by_this_permission_middleware_if_have_one_of_the_permissions()
    {
        Auth::login($this->testUser);

        $this->testUser->givePermissionTo('edit-articles');

        $this->assertEquals(
            $this->runMiddleware(
                $this->permissionMiddleware,
                'edit-news|edit-articles'
            ),
            200
        );

        $this->assertEquals(
            $this->runMiddleware(
                $this->permissionMiddleware,
                ['edit-news', 'edit-articles']
            ),
            200
        );
    }

    /** @test */
    public function a_user_cannot_access_a_route_protected_by_the_permission_middleware_if_have_a_different_permission()
    {
        Auth::login($this->testUser);

        $this->testUser->givePermissionTo('edit-articles');

        $can_logs = [false, true];
        $show_permissions = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            $this->assertEquals(
                $this->runMiddleware(
                    $this->permissionMiddleware,
                    'edit-news'
                ),
                403
            );

            foreach ($show_permissions as $show_permission) {
                config('permission.display_permission_in_exception', $show_permission);
                $message = $this->helpers->getUnauthorizedPermissionMessage('edit-news');
                $this->assertShowPermission($message, 'edit-news');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function a_user_cannot_access_a_route_protected_by_permission_middleware_if_have_not_permissions()
    {
        Auth::login($this->testUser);

        $can_logs = [false, true];
        $show_permissions = [true, false];

        foreach ($can_logs as $can_log) {
            config('permission.log_registration_exception', $can_log);

            $this->assertEquals(
                $this->runMiddleware(
                    $this->permissionMiddleware,
                    'edit-articles|edit-news'
                ),
                403
            );

            foreach ($show_permissions as $show_permission) {
                config('permission.display_permission_in_exception', $show_permission);
                $message = $this->helpers->getUnauthorizedPermissionMessage('edit-articles, edit-news');
                $this->assertShowPermission($message, 'edit-articles');
                $this->assertShowPermission($message, 'edit-news');
                $this->assertLogMessage($message, Level::Alert);
            }
        }
    }

    /** @test */
    public function the_required_roles_can_be_fetched_from_the_exception()
    {
        Auth::login($this->testUser);
        $requiredRoles = [];
        try {
            $this->roleMiddleware->handle(new Request(), function () {
                return (new Response())->setContent('<html></html>');
            }, 'testRole');
        } catch (UnauthorizedException $e) {
            $requiredRoles = $e->getRequiredRoles();
        }
        $this->assertEquals(['testRole'], $requiredRoles);
    }
    /** @test */
    public function the_required_permissions_can_be_fetched_from_the_exception()
    {
        Auth::login($this->testUser);
        $requiredPermissions = [];
        try {
            $this->permissionMiddleware->handle(new Request(), function () {
                return (new Response())->setContent('<html></html>');
            }, 'edit-articles');
        } catch (UnauthorizedException $e) {
            $requiredPermissions = $e->getRequiredPermissions();
        }
        $this->assertEquals(['edit-articles'], $requiredPermissions);
    }

    protected function runMiddleware($middleware, $parameter)
    {
        try {
            return $middleware->handle(new Request(), function () {
                return (new Response())->setContent('<html></html>');
            }, $parameter)->status();
        } catch (UnauthorizedException $e) {
            return $e->getStatusCode();
        }
    }
}
