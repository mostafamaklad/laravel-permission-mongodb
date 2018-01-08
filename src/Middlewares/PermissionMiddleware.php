<?php
declare(strict_types=1);

namespace Maklad\Permission\Middlewares;

use Closure;
use Maklad\Permission\Exceptions\UserNotLoggedIn;
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
     * @throws \Maklad\Permission\Exceptions\UnauthorizedException
     */
    public function handle($request, Closure $next, $permission)
    {
        if (auth()->guest()) {
            $helpers = new Helpers();
            throw new UserNotLoggedIn(403, $helpers->getUserNotLoggedINMessage());
        }

        $permissions = \is_array($permission) ? $permission : \explode('|', $permission);


        if (! auth()->user()->hasAnyPermission($permissions)) {
            $helpers = new Helpers();
            throw new UserNotLoggedIn(403, $helpers->getUnauthorizedPermissionMessage(implode(', ', $permissions)));
        }

        return $next($request);
    }
}
