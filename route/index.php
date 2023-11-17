<?php

use app\middleware\AllowOriginMiddleware;
use app\middleware\AuthTokenMiddleware;
use think\facade\Route;

//前端接口
Route::group(
    'web',
    function () {
        //手机账号密码登录
        Route::post('login/mobile', 'index.Login/login');
        Route::post('register', 'index.Account/register');                 //用户注册
        Route::get('oauth/qrcode', 'index.Login/getLoginKey');             //获取扫码登录key
        Route::get('oauth/qrcode/scan/:key', 'index.Login/scanLogin');     //检测扫码情况


        // 微信配置
        Route::group(
            'wx',
            function () {
                //公众号 jssdk
                Route::post('jssdk/get', 'index.Wechat/jsConfig');
                //公众号 授权登录url
                Route::post('user/auth', 'index.Wechat/wxUserInfoLoginUrl');
                //公众号 显式授权登录
                Route::post('user/code/decode', 'index.Wechat/wxUserInfoLoginCodeDecode');
                //公众号 获取静默授权地址
                Route::post('user/base/login', 'index.Wechat/wxBaseLoginUrl');
                //公众号 解析静默授权code
                Route::post('user/base/decode', 'index.Wechat/wxBaseLoginCodeDecode');
            }
        );
        // 商品
        Route::group(
            'goods',
            function () {
                Route::get('list', 'index.Goods/list');             //商品列表
                Route::get('details/:id', 'index.Goods/details');   //商品详细
            }
        );

        //需要登录操作
        Route::group(
            function () {
                Route::post('login/code/set/:code', 'index.Login/setLoginKey');     //设置扫码记录
                Route::post('login/code/confirm/:code', 'index.Login/setLoginCode'); //确认扫码
                // 用户
                Route::group(
                    'user',
                    function () {
                        Route::get('info', 'index.User/info');            //用户信息
                        Route::put('info/edit', 'index.User/setInfo');    //修改用户信息
                    }
                );
                //下单
                Route::group(
                    'order',
                    function () {
                        Route::post('create', 'index.Order/createOrder');   //创建订单
                        Route::get('list', 'index.Order/orderList');        //订单列表
                        Route::get('/:id', 'index.Order/orderDetails');     //订单详细
                    }
                );

                //gpt
                Route::group(
                    'gpt',
                    function () {
                        Route::get('group/list', 'index.Chat/getGroupList');    //分组列表
                        Route::get('group/:id', 'index.Chat/getGroupChatMsg');  //分组详细
                        Route::put('group/:id', 'index.Chat/editGroup');        //编辑分组信息
                        Route::delete('group/:id', 'index.Chat/delGroup');      //删除分组
                        Route::post('send/text', 'index.Chat/sendText');        //发送信息
                    }
                );
                Route::group(
                    'pay',
                    function () {
                        Route::post('pay', 'index.Payment/pay');            //支付
                        Route::post('polling', 'index.Payment/polling');    //公众号轮询订单是否支付
                    }
                );
            }
        )->middleware(AuthTokenMiddleware::class, 'web');

        //微信回调接口
        Route::post('pay/oa/notify', 'index.Payment/notifyOa');             //公众号支付回调

    }
)->middleware(AllowOriginMiddleware::class);
