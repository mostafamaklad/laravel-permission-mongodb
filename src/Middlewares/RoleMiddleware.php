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

        $role = \explode(';', $param);

        $organization = null;
        if(!empty($role[1])){
            $organization = Organization::where('_id', $role[1])->first();
        }

        $roles = \explode('|', $role[0]);

        if (! app('auth')->user()->hasAnyRole($organization, $roles)) {
            $helpers = new Helpers();
            throw new UnauthorizedRole(403, $helpers->getUnauthorizedRoleMessage(implode(', ', $roles)), $roles);
        }

        return $next($request);
    }
}
