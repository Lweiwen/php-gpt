<?php

namespace app\validate\admin;

use think\Validate;

class GoodsValidata extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'goods_name|商品名称'         => ['require', 'max:50'],
        'price|会员价'               => ['require', 'float', 'egt:0'],
        'original_price|原价'       => ['require', 'float', 'egt:0'],
        'sale_status|上架'          => ['require', 'in:0,1'],
        'ai_num|ai次数'             => ['require', 'egt:1'],
        'sale_status_select|上架筛选' => ['in:-1,0,1'],
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
        'add'              => ['goods_name', 'price', 'original_price', 'sale_status', 'ai_num'],
        'select'           => ['sale_status_select'],
        'edit_sale_status' => ['sale_status'],
    ];

}