<?php
// +----------------------------------------------------------------------
// | Cookie设置
// +----------------------------------------------------------------------
return [
    // cookie 保存时间
    'expire'     => 0,
    // cookie 保存路径
    'path'       => '/',
    // cookie 有效域名
    'domain'     => '',
    // cookie 启用安全传输
    'secure'     => false,
    // httponly设置
    'httponly'   => false,
    // 是否使用 setcookie
    'setcookie'  => true,
    // 跨域header
    'header'     => [
        'Access-Control-Allow-Origin'  => '*',
        'Access-Control-Allow-Headers' => 'Authorization, Origin, X-Requested-With, Content-Type, Accept',
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE',
        'Content-Type'                 => 'application/json'
    ],
    // token名称
    'token_name' => 'Authorization',
];
