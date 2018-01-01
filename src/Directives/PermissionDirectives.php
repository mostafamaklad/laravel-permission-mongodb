<?php

namespace Maklad\Permission\Directives;

use Illuminate\View\Compilers\BladeCompiler;

/**
 * Class PermissionDirectives
 * @package Maklad\Permission\Directives
 */
class PermissionDirectives
{
    private $bladeCompiler;

    public function __construct(BladeCompiler $bladeCompiler)
    {
        $this->bladeCompiler = $bladeCompiler;
    }

    public function role_directive()
    {
        $this->bladeCompiler->directive('role', function ($arguments) {
            $arguments = preg_replace('(\(|\)| )', '', $arguments);
            list($role, $guard) = \explode(',', $arguments . ',');

            return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
        });

        $this->bladeCompiler->directive('endrole', function () {
            return '<?php endif; ?>';
        });
    }

    public function hasrole_directive()
    {
        $this->bladeCompiler->directive('hasrole', function ($arguments) {
            $arguments = preg_replace('(\(|\)| )', '', $arguments);
            list($role, $guard) = \explode(',', $arguments . ',');

            return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
        });
        $this->bladeCompiler->directive('endhasrole', function () {
            return '<?php endif; ?>';
        });
    }

    public function hasanyrole_directive()
    {
        $this->bladeCompiler->directive('hasanyrole', function ($arguments) {
            $arguments = preg_replace('(\(|\)| )', '', $arguments);
            list($roles, $guard) = \explode(',', $arguments . ',');

            return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasAnyRole({$roles})): ?>";
        });
        $this->bladeCompiler->directive('endhasanyrole', function () {
            return '<?php endif; ?>';
        });
    }

    public function hasallroles_directive()
    {
        $this->bladeCompiler->directive('hasallroles', function ($arguments) {
            $arguments = preg_replace('(\(|\)| )', '', $arguments);
            list($roles, $guard) = \explode(',', $arguments . ',');

            return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasAllRoles({$roles})): ?>";
        });
        $this->bladeCompiler->directive('endhasallroles', function () {
            return '<?php endif; ?>';
        });
    }
}