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

    public function roleDirective()
    {
        $this->bladeCompiler->directive('role', function ($arguments) {
            list($role, $guard) = $this->extractRoleGuard($arguments);

            return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
        });

        $this->bladeCompiler->directive('endrole', function () {
            return '<?php endif; ?>';
        });
    }

    public function hasroleDirective()
    {
        $this->bladeCompiler->directive('hasrole', function ($arguments) {
            list($role, $guard) = $this->extractRoleGuard($arguments);

            return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
        });
        $this->bladeCompiler->directive('endhasrole', function () {
            return '<?php endif; ?>';
        });
    }

    public function hasanyroleDirective()
    {
        $this->bladeCompiler->directive('hasanyrole', function ($arguments) {
            list($roles, $guard) = $this->extractRoleGuard($arguments);

            return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasAnyRole({$roles})): ?>";
        });
        $this->bladeCompiler->directive('endhasanyrole', function () {
            return '<?php endif; ?>';
        });
    }

    public function hasallrolesDirective()
    {
        $this->bladeCompiler->directive('hasallroles', function ($arguments) {
            list($roles, $guard) = $this->extractRoleGuard($arguments);

            return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasAllRoles({$roles})): ?>";
        });
        $this->bladeCompiler->directive('endhasallroles', function () {
            return '<?php endif; ?>';
        });
    }

    private function extractRoleGuard($arguments){
        $arguments = preg_replace('(\(|\)| )', '', $arguments);
        return \explode(',', $arguments . ',');
    }
}