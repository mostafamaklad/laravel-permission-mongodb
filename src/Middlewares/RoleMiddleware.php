<?php
declare(strict_types=1);

namespace Maklad\Permission\Middlewares;

use Closure;
use Maklad\Permission\Exceptions\UnauthorizedRole;
use Maklad\Permission\Exceptions\UserNotLoggedIn;
use Maklad\Permission\Helpers;
use Maklad\Permission\Models\Organization;

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
    public function handle($request, Closure $next, $param)
    {
        if (app('auth')->guest()) {
            $helpers = new Helpers();
            throw new UserNotLoggedIn(403, $helpers->getUserNotLoggedINMessage());
        }

        $roles = \explode('|', $param);

        if (! app('auth')->user()->hasAnyRole($roles, null)) {
            $helpers = new Helpers();
            throw new UnauthorizedRole(403, $helpers->getUnauthorizedRoleMessage(implode(', ', $roles)), $roles);
        }

        return $next($request);
    }
}
