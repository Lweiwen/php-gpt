<?php

namespace app\validate\admin;

use think\Validate;

class AdminValidata extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'account|账号'     => ['require'],
        'password|密码'   => 'require',
        'realname|真实名字' => 'require',
        'roles|权限'      => 'require',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [

    ];

    /**
     * 需要验证的规则方法
     * @var \string[][]
     */
    protected $scene = [
        'get' => ['account', 'password'],
    ];


}
