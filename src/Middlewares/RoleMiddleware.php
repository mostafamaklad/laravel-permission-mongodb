<?php
declare(strict_types=1);

namespace Maklad\Permission\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Class RoleMiddleware
 * @package Maklad\Permission\Middlewares
 */
class RoleMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @param $role
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $role)
    {
        if (Auth::guest()) {
            \abort(403);
        }

        $roles = \is_array($role) ? $role : \explode('|', $role);

        if (! Auth::user()->hasAnyRole($roles)) {
            \abort(403);
        }

        return $next($request);
    }
}
