<?php

namespace Maklad\Permission\Test;

use Illuminate\Support\Facades\DB;
use Maklad\Permission\Models\Permission;
use Maklad\Permission\Models\Role;
use Maklad\Permission\PermissionRegistrar;
use PHPUnit\Framework\Attributes\Test;

class CacheTest extends TestCase
{
    protected mixed $registrar;

    public function setUp(): void
    {
        parent::setUp();

        $this->registrar = app(PermissionRegistrar::class);

        $this->registrar->forgetCachedPermissions();

        // Since Laravel 12, migrate:fresh disconnects the connection and the MongoDB one can't log queries until rebuilt
        DB::purge('mongodb');
        DB::connection('mongodb')->enableQueryLog();

        $this->assertCount(0, DB::getQueryLog());

        $this->registrar->registerPermissions();

        $this->assertCount(1, DB::getQueryLog());

        DB::flushQueryLog();
    }

    #[Test]
    public function it_can_cache_the_permissions()
    {
        $this->registrar->registerPermissions();

        $this->assertCount(0, DB::getQueryLog());
    }

    #[Test]
    public function permission_creation_and_updating_and_deleting_should_flush_the_cache()
    {
        $permission = app(Permission::class)->create(['name' => 'new']);
        $this->assertCount(1, DB::getQueryLog());

        $this->registrar->registerPermissions();
        $this->assertCount(2, DB::getQueryLog());

        $permission->name = 'other name';
        $permission->save();
        $this->assertCount(3, DB::getQueryLog());

        $this->registrar->registerPermissions();
        $this->assertCount(4, DB::getQueryLog());

        $permission->delete();
        $this->assertCount(5, DB::getQueryLog());

        $this->registrar->registerPermissions();
        $this->assertCount(6, DB::getQueryLog());
    }

    #[Test]
    public function role_creation_and_updating_and_deleting_should_flush_the_cache()
    {
        $role = app(Role::class)->create(['name' => 'new']);
        $this->assertCount(2, DB::getQueryLog());

        $this->registrar->registerPermissions();
        $this->assertCount(3, DB::getQueryLog());

        $role->name = 'other name';
        $role->save();
        $this->assertCount(4, DB::getQueryLog());

        $this->registrar->registerPermissions();
        $this->assertCount(5, DB::getQueryLog());

        $role->delete();
        $this->assertCount(6, DB::getQueryLog());

        $this->registrar->registerPermissions();
        $this->assertCount(7, DB::getQueryLog());
    }

    #[Test]
    public function user_creation_should_not_flush_the_cache()
    {
        User::create(['email' => 'new']);
        $this->assertCount(1, DB::getQueryLog());

        $this->registrar->registerPermissions();
        $this->assertCount(1, DB::getQueryLog());
    }

    #[Test]
    public function adding_a_permission_to_a_role_should_flush_the_cache()
    {
        $this->testUserRole->givePermissionTo($this->testUserPermission);
        $this->assertCount(1, DB::getQueryLog());

        $this->registrar->registerPermissions();
        $this->assertCount(2, DB::getQueryLog());
    }

    #[Test]
    public function has_permission_to_should_use_the_cache()
    {
        $this->testUserRole->givePermissionTo(['edit-articles', 'edit-news']);
        $this->testUser->assignRole('testRole');
        $this->assertCount(4, DB::getQueryLog());

        $this->assertTrue($this->testUser->hasPermissionTo('edit-articles'));
        $this->assertCount(8, DB::getQueryLog());

        $this->assertTrue($this->testUser->hasPermissionTo('edit-news'));
        $this->assertCount(10, DB::getQueryLog());

        // The permission comes from the cache, but as a new model whose roles relation is loaded again
        $this->assertTrue($this->testUser->hasPermissionTo('edit-articles'));
        $this->assertCount(12, DB::getQueryLog());
    }

    #[Test]
    public function it_can_read_the_permissions_from_a_cache_that_does_not_unserialize_objects()
    {
        // New Laravel 12.x/13.x applications ship with this setting, which only matters for serializing stores
        $this->app['config']->set('cache.serializable_classes', false);
        $this->app['config']->set('cache.default', 'file');
        $this->app['cache']->forgetDriver('file');
        $this->app->forgetInstance('cache.store');

        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
        $registrar->getPermissions();

        $permissions = $registrar->getPermissions();

        $this->assertContainsOnlyInstancesOf(Permission::class, $permissions);
        $this->assertEqualsCanonicalizing(
            ['edit-articles', 'edit-news', 'edit-categories', 'admin-permission'],
            $permissions->pluck('name')->all()
        );

        $this->testUser->givePermissionTo('edit-articles');

        $this->assertTrue($this->testUser->fresh()->hasPermissionTo('edit-articles'));

        $registrar->forgetCachedPermissions();
    }

    #[Test]
    public function it_ignores_and_forgets_the_permissions_cached_by_older_releases()
    {
        $this->app['cache']->put('maklad.permission.cache', collect([new Permission(['name' => 'stale-permission'])]));

        $this->assertNotContains('stale-permission', $this->registrar->getPermissions()->pluck('name'));

        $this->registrar->forgetCachedPermissions();

        $this->assertFalse($this->app['cache']->has('maklad.permission.cache'));
    }
}
