<?php

namespace app\utils;

use think\facade\Config;
use think\Response;

/**
 * Json 输出类
 * Class Json
 * @package app\utils
 */
class Json
{
    private $code = 200;

    public function code(int $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function make(int $code, string $msg, array $data = [], ?array $header = []): Response
    {
//        $header = [
//            'Access-Control-Allow-Origin'  => '*',
//            'Access-Control-Allow-Headers' => 'Authorization, Origin, X-Requested-With, Content-Type, Accept',
//            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE',
//            'Content-Type'                 => 'application/json'
//        ];
        $header = Config::get('cookie.header');
        $res = compact('code', 'msg', 'data');
        return Response::create($res, 'json', $this->code)->header($header);
    }

    public function apiResponse($msg = 'success', int $code = 1, array $data = [], ?array $header = []): Response
    {
        return $this->make($code, $msg, $data, $header);
    }

}
