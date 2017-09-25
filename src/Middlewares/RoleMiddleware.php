<?php
declare(strict_types=1);

namespace Maklad\Permission\Middlewares;

use Closure;

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
        if (auth()->guest()) {
            \abort(403);
        }

        $roles = \is_array($role) ? $role : \explode('|', $role);

        if (! auth()->user()->hasAnyRole($roles)) {
            \abort(403);
        }

        return $next($request);
    }
}
