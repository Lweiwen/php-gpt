<?php

namespace app\middleware;


use app\interfaces\MiddlewareInterface;
use app\Request;

/**
 * Class BaseMiddleware
 * @package app\api\middleware
 */
class BaseMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next, bool $force = true)
    {
        if (!Request::hasMacro('uid')) {
            Request::macro(
                'uid',
                function () {
                    return 0;
                }
            );
        }
        return $next($request);
    }
}
