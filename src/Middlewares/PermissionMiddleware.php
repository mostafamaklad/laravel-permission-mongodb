<?php
declare(strict_types=1);

namespace Maklad\Permission\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;
use Maklad\Permission\Helpers;

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
        $helpers = new Helpers();
        if (Auth::guest()) {
            $helpers->abort(403);
        }

        $permissions = \is_array($permission) ? $permission : \explode('|', $permission);


        if (! Auth::user()->hasAnyPermission($permissions)) {
            $helpers->abort(403);
        }

        return $next($request);
    }
}
