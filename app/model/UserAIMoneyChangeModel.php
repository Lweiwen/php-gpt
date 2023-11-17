<?php

namespace app\model;

use think\model\concern\SoftDelete;

class UserAIMoneyChangeModel extends BaseModel
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'user_ai_money_change';

    use SoftDelete;


    /*******************************
     ** 次数变动：1~5
     *******************************/
    const admin_add = 1;
    const admin_reduce = 2;
    const user_buy = 3;
    const user_use = 4;
    const cancel_order_refund = 5;
    const default_add = 6;

    /**
     * 返回对应操作解释
     * @return string|string[]
     * @author LWW
     */
    public static function getDesc($from = true)
    {
        $desc = [
            self::admin_add           => '系统增加次数',
            self::admin_reduce        => '系统扣减次数',
            self::user_buy            => '用户购买次数',
            self::cancel_order_refund => '取消订单退回次数',
            self::user_use            => '用户使用减次数',
            self::default_add         => '系统默认次数'
        ];
        if ($from === true) {
            return $desc;
        }
        return $desc[$from] ?? '';
    }

    /**
     * 新增变化
     * @param array $data
     * @return int|string
     * @author LWW
     */
    public static function createChange(array $data)
    {
        return self::create($data);
    }

}