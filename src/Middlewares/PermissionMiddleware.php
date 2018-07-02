<?php
declare(strict_types=1);

namespace Maklad\Permission\Middlewares;

use Closure;
use Maklad\Permission\Exceptions\UnauthorizedPermission;
use Maklad\Permission\Exceptions\UserNotLoggedIn;
use Maklad\Permission\Helpers;
use Maklad\Permission\Models\Organization;

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
    public function handle($request, Closure $next, $param)
    {
        if (app('auth')->guest()) {
            $helpers = new Helpers();
            throw new UserNotLoggedIn(403, $helpers->getUserNotLoggedINMessage());
        }

        $permission = \explode(';', $param);

        $organization = null;
        if(!empty($permission[1])){
            $organization = Organization::where('_id', $permission[1])->first();
        }

        $permissions = \explode('|', $permission[0]);


        if (! app('auth')->user()->hasAnyPermission($organization, $permissions)) {
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
