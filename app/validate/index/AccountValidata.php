<?php

namespace app\validate\index;

use think\Validate;

class AccountValidata extends Validate
{
//    protected $regex = ['password' => '^(?=.*[a-zA-Z0-9].*)(?=.*[a-zA-Z\\W].*)(?=.*[0-9\\W].*).{6,20}$'];

    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'mobile|手机'       => 'require|mobile',
        'password|密码'     => 'require|length:6,30',
        'repassword|再次密码' => 'require|confirm:password',
    ];

    /**
     * 错误信息
     * @var string[]
     */
    protected $message = [
        'mobile.require'     => '请输入手机号',
        'password.require'   => '请输入密码',
        'password.regex'     => '密码格式错误',
        'password.length'    => '密码应为6-30长度',
        'code.requireIf'     => '请输入验证码',
        'repassword.confirm' => '两次输入密码不一致'
//        'mobile.mobile'    => '非有效手机号码'
    ];

    /**
     * 需要验证的规则方法
     * @var \string[][]
     */
    protected $scene = [
        'register' => ['mobile', 'password', 'repassword'],
        'login'    => ['mobile', 'password'],
    ];

}