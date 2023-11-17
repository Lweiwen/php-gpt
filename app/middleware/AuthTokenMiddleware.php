<?php

namespace app\middleware;

use app\exceptions\ApiException;
use app\interfaces\MiddlewareInterface;
use app\Request;
use app\services\auth\AuthGuard;

class AuthTokenMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next, ...$arrAuthPath)
    {
        $token = trim(ltrim($request->header(config('cookie.token_name', 'Authorization')), 'Bearer'));

        //登录认证路径
        $authPath = '';
        //如果不传入登录认证参数，则根据jwt密文信息中的登录认证参数
        //如果传入多个登录认证路径参数，获取第一个参数，表示指定必须使用登录认证参数
        if (count($arrAuthPath) == 1) {
            if (is_string($arrAuthPath[0])) {
                $authPath = $arrAuthPath[0];
            }
            //如果第一个参数无效，则默认使用web路径
            if (empty($authPath)) {
                $authPath = 'web';
            }
        }
        $objGuard = new AuthGuard($token);
        if (!$objGuard->checkAndSetAuthPath($authPath, $arrAuthPath)) {
            throw new ApiException('登录路径错误', 1001);
        }
        if (!$objGuard->check()) {
            throw new ApiException('请进行登录', 1001);
        }
        return $next($request);
    }
}