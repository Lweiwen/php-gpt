<?php

namespace app\model;

use think\model\concern\SoftDelete;

class TradeOrderModel extends BaseModel
{
    use SoftDelete;

    const native = 1;
    const jsapi = 2;

    const STATE_CREATE = 0; //状态:待支付
    const LOG_STATE_COMPLETE = 1; //状态:完成
    const LOG_STATE_FAILURE = 2; //状态:失败
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'trade_order';

    /**
     * 新增数据
     * @param array $request
     * @return TradeOrderModel|\think\Model
     * @author LWW
     */
    public static function add(array $request)
    {
        return self::create($request);
    }

    /**
     * 根据订单号查找数据
     * @param string $orderSn
     * @param string $outOrderSn
     * @return TradeOrderModel|array|mixed|\think\db\Query|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findWithOrderSnForLocking(string $orderSn = '', string $outOrderSn = '')
    {
        $where = [];
        if (!empty($orderSn)) {
            $where[] = ['order_sn', '=', $orderSn];
        }
        if (!empty($outOrderSn)) {
            $where[] = ['out_trade_no', '=', $outOrderSn];
        }
        return self::where($where)->lock(true)->find();
    }

    /**
     * 编辑支付信息
     * @param TradeOrderModel $obj
     * @param array $param
     * @return bool
     * @author LWW
     */
    public static function edit(self $obj, array $param)
    {
        foreach ($param as $key => $val) {
            $obj->$key = $val;
        }
        return $obj->save();
    }
}