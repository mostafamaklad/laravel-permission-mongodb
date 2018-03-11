<?php
declare(strict_types=1);

namespace Maklad\Permission\Middlewares;

use Closure;
use Maklad\Permission\Exceptions\UnauthorizedRole;
use Maklad\Permission\Exceptions\UserNotLoggedIn;
use Maklad\Permission\Helpers;

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
     * @throws \Maklad\Permission\Exceptions\UnauthorizedException
     */
    public function handle($request, Closure $next, $role)
    {
        if (app('auth')->guest()) {
            $helpers = new Helpers();
            throw new UserNotLoggedIn(403, $helpers->getUserNotLoggedINMessage());
        }

        $roles = \is_array($role) ? $role : \explode('|', $role);

        if (! app('auth')->user()->hasAnyRole($roles)) {
            $helpers = new Helpers();
            throw new UnauthorizedRole(403, $helpers->getUnauthorizedRoleMessage(implode(', ', $roles)), $roles);
        }

        return $next($request);
    }
}
