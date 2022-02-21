<?php

namespace Maklad\Permission\Test;

use Illuminate\Database\Seeder;
use Illuminate\Foundation\Application;
use Maklad\Permission\Models\Permission;
use Maklad\Permission\Models\Role;

class TestSeeder extends Seeder {

    private Application $app;

    public function __construct(Application $app) {
        $this->app = $app;
    }

    /**
     * Run the database seeds.
     */
    public function run() {
        User::create(['email' => 'test@user.com']);
        Admin::create(['email' => 'admin@user.com']);
        $this->app[Role::class]->create(['name' => 'testRole']);
        $this->app[Role::class]->create(['name' => 'testRole2']);
        $this->app[Role::class]->create(['name' => 'testAdminRole', 'guard_name' => 'admin']);
        $this->app[Permission::class]->create(['name' => 'edit-articles']);
        $this->app[Permission::class]->create(['name' => 'edit-news']);
        $this->app[Permission::class]->create(['name' => 'edit-categories']);
        $this->app[Permission::class]->create(['name' => 'admin-permission', 'guard_name' => 'admin']);
    }

}
