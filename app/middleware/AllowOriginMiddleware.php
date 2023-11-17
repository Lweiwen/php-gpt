<?php

namespace app\middleware;


use app\interfaces\MiddlewareInterface;
use app\Request;
use think\facade\Config;
use think\Response;

/**
 * 跨域中间件
 * Class AllowOriginMiddleware
 * @package app\http\middleware
 */
class AllowOriginMiddleware implements MiddlewareInterface
{

    /**
     * 允许跨域的域名
     * @var string
     */
    protected $cookieDomain;

    /**
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle(Request $request, \Closure $next)
    {
        //todo:: 后期改为cookie控制
        $this->cookieDomain = Config::get('cookie.domain', '');
        $header = Config::get('cookie.header');
        $origin = $request->header('origin');
        //todo::后期判断
//        if ($origin && ('' == $this->cookieDomain || strpos($origin, $this->cookieDomain))) {
//            $header['Access-Control-Allow-Origin'] = $origin;
//        }
        if ($request->method(true) == 'OPTIONS') {
            $response = Response::create('ok')->code(200)->header($header);
        } else {
            $response = $next($request)->header($header);
        }
        return $response;
    }
}
