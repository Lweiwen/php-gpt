<?php

namespace app\model;

use think\model\concern\SoftDelete;

class OrderGoodsModel extends BaseModel
{
    use SoftDelete;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'order_goods';

    /**
     * 新增订单商品数据
     * @param array $data
     * @return OrderGoodsModel|\think\Model
     * @author LWW
     */
    public static function addOrderGoods(array $data)
    {
        return self::create($data);
    }

    /**
     * 更具订单ID查找数据
     * @param int $orderId
     * @return OrderGoodsModel|array|mixed|\think\db\Query|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findByOrderId(int $orderId)
    {
        return self::where('order_id', '=', $orderId)->find();
    }
}