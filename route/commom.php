<?php


use app\middleware\AuthTokenMiddleware;
use think\facade\Route;

Route::group(
    '/',
    function () {
        //文件上传
        Route::post('attachment/upload', 'Attachment/upload')->middleware(AuthTokenMiddleware::class);
    }
);