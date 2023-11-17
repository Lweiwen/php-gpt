<?php

namespace app\model;

use think\db\Query;
use think\model\concern\SoftDelete;

class OrderModel extends BaseModel
{
    const CANCELTIME = 1800;    //订单过期时间  默认是30分钟

    const UNPAID = 0;//待支付
    const ISPAID = 1;//已支付
    const REFUNDED = 2;//已退款
    const REFUSED_REFUND = 3;//拒绝退款

    //支付方式
    const WECHAT_PAY = 1;//微信支付
    const ALI_PAY = 2;//支付宝支付
    const BALANCE_PAY = 3;//余额支付

    //订单是否关闭
    const ORDER_NORMAL = 1;  //订单正常
    const ORDER_CLOSE = 0;   //订单关闭

    use SoftDelete;

    /**
     * 关联订单商品
     * @return \think\model\relation\HasOne
     * @author LWW
     */
    public function orderGoods()
    {
        return $this->hasOne(OrderGoodsModel::class, 'order_id', 'id');
    }

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'order';

    /**
     * 新增订单数据
     * @param array $orderData
     * @return int|string
     * @author LWW
     */
    public static function createOrder(array $orderData)
    {
        return self::insertGetId($orderData);
    }

    /**
     * 获取一条订单数据
     * @param int $id
     * @return UserAuthModel|array|mixed|Query|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findWithId(int $id)
    {
        return self::where('id', '=', $id)->find();
    }

    /**
     * 根据订单号查询数据
     * @param string $orderSn
     * @param bool $lock
     * @return OrderModel|array|mixed|Query|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findWithOrderSn(string $orderSn, bool $lock = false)
    {
        $obj = self::where('order_sn', '=', $orderSn);
        if ($lock) {
            $obj->lock($lock);
        }
        return $obj->find();
    }

    /**
     * 获取列表
     * @param int $limit
     * @param int $offset
     * @param array $param
     * @param array $order
     * @param array $with
     * @param array|string[] $filed
     * @return OrderModel[]|array|\think\Collection|Query[]|\think\model\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function listForOrder(
        int $limit,
        int $offset,
        array $param = [],
        array $order = [],
        array $with = [],
        array $filed = ['*']
    ) {
        $obj = self::dealForSelect($param);
        if ($limit > 0) {
            $obj->limit($offset, $limit);
        }
        if (!empty($order)) {
            foreach ($order as $key => $val) {
                $obj->order($key, $val);
            }
        }
        if (!empty($with)) {
            $obj->with($with);
        }

        return $obj->field($filed)->select();
    }

    /**
     * 返回订单数量
     * @param array $param
     * @return int|Query
     * @throws \think\db\exception\DbException
     * @author LWW
     */
    public static function countForOrder(array $param)
    {
        return self::dealForSelect($param)->count();
    }

    /**
     * 处理搜寻条件
     * @param array $param
     * @return OrderModel|Query
     * @author LWW
     */
    public static function dealForSelect(array $param)
    {
        $where = [];
        if (isset($param['user_id']) && !empty($param['user_id'])) {
            $where[] = ['user_id', '=', $param['user_id']];
        }
        if (isset($param['order_type']) && $param['order_type'] >= 0) {
            $where[] = ['order_type', '=', $param['order_type']];
        }
        if (isset($param['order_sn']) && !empty($param['order_sn'])) {
            $where[] = ['order_sn', '=', $param['order_sn']];
        }
        if (isset($param['pay_status']) && $param['pay_status'] >= 0) {
            $where[] = ['pay_status', '=', $param['pay_status']];
        }
        if (isset($param['order_status'])) {
            $where[] = ['order_status', '=', $param['order_status']];
        }
        if (isset($param['begin_time']) && !empty($param['begin_time'])) {
            $where[] = ['create_time', '>=', $param['begin_time']];
        }
        if (isset($param['end_time']) && !empty($param['end_time'])) {
            $where[] = ['create_time', '<', $param['end_time']];
        }
        return self::where($where);
    }

    /**
     * 更新数据
     * @param OrderModel $obj
     * @param array $data
     * @return bool
     * @author LWW
     */
    public static function edit(self $obj, array $data)
    {
        foreach ($data as $key => $val) {
            $obj->$key = $val;
        }
        return $obj->save();
    }

    /**
     * 获取某天的销售额
     * @param string $time
     * @return float|Query
     * @author LWW
     */
    public static function todaySales(string $time)
    {
        return self::whereTime('create_time', $time)
            ->where('pay_status', 1)
            ->where('order_status', 1)
            ->sum('order_amount');
    }

    /**
     * 获取某天的订单数量
     * @param string $time
     * @return int|Query
     * @throws \think\db\exception\DbException
     * @author LWW
     */
    public static function todaySalesCount(string $time)
    {
        return self::whereTime('create_time', $time)
            ->where('pay_status', 1)
            ->where('order_status', 1)
            ->count();
    }

    /**
     * 根据用户id获取累计消费
     * @param array $userIds
     * @return OrderModel[]|array|\think\Collection|Query[]|\think\model\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function sumForUserIds(array $userIds)
    {
        return self::whereIn('user_id', $userIds)
            ->field(['sum(order_amount) as order_amount','user_id'])
            ->where('pay_status', '=', 1)
            ->where('order_status', '=', 1)
            ->group('user_id')
            ->select();
    }

}
