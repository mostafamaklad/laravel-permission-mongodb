<?php

namespace Maklad\Permission\Test;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use MongoDB\Laravel\MongoDBServiceProvider;
use Maklad\Permission\Helpers;
use Maklad\Permission\Models\Permission;
use Maklad\Permission\Models\Role;
use Maklad\Permission\PermissionRegistrar;
use Maklad\Permission\PermissionServiceProvider;
use Monolog\Handler\TestHandler;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use DatabaseMigrations;
    protected Helpers $helpers;

    protected string $seeder = TestSeeder::class;

    /**
     * Flush the database after each test function
     */
    public function tearDown(): void
    {
        User::query()->truncate();
        Admin::query()->truncate();
        $this->app[Role::class]::truncate();
        $this->app[Permission::class]::truncate();
    }

    protected User $testUser;

    protected Admin $testAdmin;

    protected Role $testUserRole;

    protected Role $testAdminRole;

    protected Permission $testUserPermission;

    protected Permission $testAdminPermission;

    public function setUp(): void
    {
        parent::setUp();

        // $this->setUpDatabase($this->app);

        $this->reloadPermissions();

        $this->testUser = User::first();
        $this->testUserRole = app(config('permission.models.role'))->where('name', 'testRole')->first();
        $this->testUserPermission = app(config('permission.models.permission'))->where('name', 'edit-articles')->first();

        $this->testAdmin = Admin::first();
        $this->testAdminRole = app(config('permission.models.role'))->where('name', 'testAdminRole')->first();
        $this->testAdminPermission = app(config('permission.models.permission'))->where('name', 'admin-permission')->first();

        $this->clearLogTestHandler();

        $this->helpers = new Helpers();
    }

    /**
     * @param Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            PermissionServiceProvider::class,
            MongodbServiceProvider::class,
        ];
    }

    /**
     * Set up the environment.
     *
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'mongodb');
        $app['config']->set('database.connections.mongodb', [
            'host' => 'localhost',
            'port' => '27017',
            'driver' => 'mongodb',
            'database' => 'laravel_permission_mongodb_test',
            'prefix' => '',
        ]);

        $app['config']->set('view.paths', [__DIR__ . '/resources/views']);

        // Set-up admin guard
        $app['config']->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'admins']);
        $app['config']->set('auth.providers.admins', ['driver' => 'eloquent', 'model' => Admin::class]);

        // Use test User model for users provider
        $app['config']->set('auth.providers.users.model', User::class);

        $app['log']->getLogger()->pushHandler(new TestHandler());
    }

    /**
     * Reload the permissions.
     *
     * @return bool
     */
    protected function reloadPermissions(): bool
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return app(PermissionRegistrar::class)->registerPermissions();
    }

    /**
     * Refresh the testUser.
     */
    public function refreshTestUser()
    {
        $this->testUser = $this->testUser->fresh();
    }

    /**
     * Refresh the testAdmin.
     */
    public function refreshTestAdmin()
    {
        $this->testAdmin = $this->testAdmin->fresh();
    }

    protected function clearLogTestHandler()
    {
        collect($this->app['log']->getLogger()->getHandlers())->filter(function ($handler) {
            return $handler instanceof TestHandler;
        })->first()->clear();
    }

    protected function assertNotLogged($message, $level)
    {
        $this->assertFalse($this->hasLog($message, $level));
    }

    protected function assertLogged($message, $level)
    {
        $this->assertTrue($this->hasLog($message, $level));
    }

    /**
     * @param $message
     * @param $level
     *
     * @return bool
     */
    protected function hasLog($message, $level): bool
    {
        return collect($this->app['log']->getLogger()->getHandlers())->filter(function ($handler) use (
            $message,
            $level
        ) {
            return $handler instanceof TestHandler && $handler->hasRecordThatContains($message, $level);
        })->count() > 0;
    }

    /**
     * @param $message
     * @param $level
     */
    protected function assertLogMessage($message, $level)
    {
        if (config('permission.log_registration_exception')) {
            $this->assertLogged($message, $level);
        } else {
            $this->assertNotLogged($message, $level);
        }
    }

    /**
     * @param $message
     * @param $role_permission
     */
    protected function assertShowPermission($message, $role_permission)
    {
        if (config('permission.display_permission_in_exception')) {
            $this->assertContains($role_permission, $message);
        } else {
            $this->assertStringNotContainsString($role_permission, $message);
        }
    }

    /**
     * Refresh the testUserPermission.
     */
    public function refreshTestUserPermission()
    {
        $this->testUserPermission = $this->testUserPermission->fresh();
    }
}
