<?php
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
        $redirectUrl = config('permission.unauthorized_redirect_url');
        $roles = is_array($role) ? $role : explode('|', $role);

        if (auth()->guest() || ! auth()->user()->hasAnyRole($roles)) {
            if (null !== $redirectUrl) {
                return redirect($redirectUrl);
            }
            abort(403);
        }

        return $next($request);
    }
}
