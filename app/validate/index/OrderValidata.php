<?php

namespace app\validate\index;

use think\Validate;

class OrderValidata extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'goods_id|商品id' => 'require|egt:1',
        'goods_num|数量'  => 'require|egt:1',
        'pay_way|支付方式'  => 'require'
    ];

    /**
     * 需要验证的规则方法
     * @var \string[][]
     */
    protected $scene = [
        'create_order' => ['goods_id', 'goods_num', 'pay_way'],
    ];
}