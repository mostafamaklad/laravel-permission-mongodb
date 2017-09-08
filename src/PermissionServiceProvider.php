<?php
declare(strict_types=1);

namespace Maklad\Permission;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Maklad\Permission\Contracts\PermissionInterface as Permission;
use Maklad\Permission\Contracts\RoleInterface as Role;

/**
 * Class PermissionServiceProvider
 * @package Maklad\Permission
 */
class PermissionServiceProvider extends ServiceProvider
{
    public function boot(PermissionRegistrar $permissionLoader)
    {
        $this->publishes([
            __DIR__ . '/../config/permission.php' => $this->app->configPath() . '/permission.php',
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\CreateRole::class,
                Commands\CreatePermission::class,
            ]);
        }

        $this->registerModelBindings();

        $permissionLoader->registerPermissions();

        $this->registerBladeExtensions();
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/permission.php',
            'permission'
        );
    }

    protected function registerModelBindings()
    {
        $config = $this->app->config['permission.models'];

        $this->app->bind(Permission::class, $config['permission']);
        $this->app->bind(Role::class, $config['role']);
    }

    protected function registerBladeExtensions()
    {
        $this->app->afterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {
            $bladeCompiler->directive('role', function ($arguments) {
                list($role, $guard) = \explode(',', $arguments . ',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
            });
            $bladeCompiler->directive('endrole', function () {
                return '<?php endif; ?>';
            });

            $bladeCompiler->directive('hasrole', function ($arguments) {
                list($role, $guard) = \explode(',', $arguments . ',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
            });
            $bladeCompiler->directive('endhasrole', function () {
                return '<?php endif; ?>';
            });

            $bladeCompiler->directive('hasanyrole', function ($arguments) {
                list($roles, $guard) = \explode(',', $arguments . ',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasAnyRole({$roles})): ?>";
            });
            $bladeCompiler->directive('endhasanyrole', function () {
                return '<?php endif; ?>';
            });

            $bladeCompiler->directive('hasallroles', function ($arguments) {
                list($roles, $guard) = \explode(',', $arguments . ',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasAllRoles({$roles})): ?>";
            });
            $bladeCompiler->directive('endhasallroles', function () {
                return '<?php endif; ?>';
            });
        });
    }
}
