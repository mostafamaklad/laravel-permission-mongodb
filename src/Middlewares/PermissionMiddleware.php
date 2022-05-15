<?php

namespace Maklad\Permission\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Maklad\Permission\Exceptions\UnauthorizedException;
use Maklad\Permission\Exceptions\UnauthorizedPermission;
use Maklad\Permission\Exceptions\UserNotLoggedIn;
use Maklad\Permission\Helpers;

/**
 * Class PermissionMiddleware
 * @package Maklad\Permission\Middlewares
 */
class PermissionMiddleware
{
    /**
     * @param Request $request
     * @param Closure $next
     * @param array|string $permission
     *
     * @return mixed
     * @throws UnauthorizedException
     */
    public function handle(Request $request, Closure $next, array|string $permission): mixed
    {
        if (app('auth')->guest()) {
            $helpers = new Helpers();
            throw new UserNotLoggedIn(403, $helpers->getUserNotLoggedINMessage());
        }

        $permissions = \is_array($permission) ? $permission : \explode('|', $permission);


        if (! app('auth')->user()->hasAnyPermission($permissions)) {
            $helpers = new Helpers();
            throw new UnauthorizedPermission(
                403,
                $helpers->getUnauthorizedPermissionMessage(implode(', ', $permissions)),
                $permissions
            );
        }

        return $next($request);
    }
}
