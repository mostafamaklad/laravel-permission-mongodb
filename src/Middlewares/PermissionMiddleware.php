<?php
declare(strict_types=1);

namespace Maklad\Permission\Middlewares;

use Closure;

/**
 * Class PermissionMiddleware
 * @package Maklad\Permission\Middlewares
 */
class PermissionMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @param $permission
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $permission)
    {
        if (auth()->guest()) {
            \abort(403);
        }

        $permissions = \is_array($permission) ? $permission : \explode('|', $permission);


        if (! auth()->user()->hasAnyPermission($permissions)) {
            \abort(403);
        }

        return $next($request);
    }
}
