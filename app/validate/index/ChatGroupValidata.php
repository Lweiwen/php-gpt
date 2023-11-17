<?php

namespace app\validate\index;

use think\Validate;

class ChatGroupValidata extends Validate
{

    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'title|名称' => 'require|max:50',
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
        'group_edit' => ['title'],
    ];
}