<?php

use app\middleware\AuthTokenMiddleware;
use think\facade\Route;

Route::group(
    'admin',
    function () {
        //不需要登录操作
        Route::post('login', 'admin.Login/login')->option(
            ['real_name' => '登录']
        );

        //需要登录操作
        Route::group(
            function () {
                // 商品
                Route::group(
                    'goods',
                    function () {
                        Route::get('list', 'admin.Goods/list');   //商品列列表
                        Route::get('details/:id', 'admin.Goods/details');  //商品详细
                        Route::post('add', 'admin.Goods/add');      //新增商品
                        Route::put('edit/:id', 'admin.Goods/edit');     //编辑山商品
                        Route::put('edit/sale_status/:id', 'admin.Goods/editSaleStatus');  //修改上下架
                    }
                );
                // 订单
                Route::group(
                    'order',
                    function () {
                        Route::get('list', 'admin.Order/list');           //订单列表
                        Route::get('details/:id', 'admin.Order/details'); //订单详细
                    }
                );
                //管理员信息
                Route::group(
                    'user',
                    function () {
                        Route::get('info', 'admin.Admin/adminInfo');
                        Route::get('list', 'admin.User/list');
                    }
                );
                Route::group(
                    'gpt',
                    function () {
                        Route::get('balance', 'admin.Gpt/balance');
                    }
                );
                Route::group(
                    'home',
                    function () {
                        Route::get('data', 'admin.Common/homeStatics');
                    }
                );
            }

        )->middleware(AuthTokenMiddleware::class, 'admin');
    }
);
