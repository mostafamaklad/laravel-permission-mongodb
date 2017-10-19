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
        $unauthorizedRedirectUrl = config('permission.unauthorized_redirect_url');
        $permissions = \is_array($permission) ? $permission : \explode('|', $permission);

        if (auth()->guest() || ! auth()->user()->hasAnyPermission($permissions)) {
            if (null !== $unauthorizedRedirectUrl) {
                return redirect($unauthorizedRedirectUrl);
            }
            \abort(403);
        }

        return $next($request);
    }
}
