<?php

namespace app\validate\index;

use think\Validate;

class ChatValidata extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'group_name|名称' => 'require|max:500',
    ];


    /**
     * 需要验证的规则方法
     * @var \string[][]
     */
    protected $scene = [
        'edit_group' => ['group_name'],
    ];
}
