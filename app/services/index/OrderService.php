<?php

namespace app\services\index;

use app\exceptions\ApiException;
use app\model\Client_;
use app\model\GoodModel;
use app\model\OrderGoodsModel;
use app\model\OrderLogModel;
use app\model\OrderModel;
use app\model\TradeOrderModel;
use app\model\UserAuthModel;
use app\services\BaseService;
use app\services\OrderLogService;
use app\services\pay\PayNotifyService;
use think\facade\Db;

class OrderService extends BaseService
{
    /**
     * 创建订单
     * @param array $post
     * @param int $userId
     * @return array
     * @throws \Throwable
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function createOrder(array $post, int $userId): array
    {
        Db::startTrans();
        try {
            $goodsId = $post['goods_id'];
            $objGood = GoodModel::findWithId($goodsId);
            if (!$objGood) {
                throw new ApiException('商品不能存在');
            }
            if ($objGood->del != 0 || $objGood->sale_status != 1) {
                throw new ApiException('爆款已下架');
            }
            $num = $post['goods_num'];//数量
            //todo::是否判断限购
            //库存判断
            $isStock = 0;
            if ($objGood->is_stock == 1) {
                $isStock = 1;
                if ($objGood->stock < $num) {
                    throw new ApiException('库存不足');
                }
            }
            $orderAmount = $totalAmount = $objGood->price * $num;
            $time = time();
            //新增订单数据
            $orderData = [
                'order_sn'     => makeOrderNo('B'),
                'user_id'      => $userId,
                'order_type'   => 0,
                'order_status' => 1,
                'pay_status'   => 0,
                'num'          => $num,
                'total_amount' => $totalAmount,
                'order_amount' => $orderAmount,
                'pay_way'      => $post['pay_way'],
                'create_time'  => $time,
            ];
            $orderId = OrderModel::createOrder($orderData);
            if ($orderId) {
                //新增订单商品数据
                $orderGoodsData = [
                    'order_id'        => $orderId,
                    'goods_id'        => $goodsId,
                    'goods_name'      => $objGood->goods_name,
                    'num'             => $num,
                    'ai_num'          => $objGood->ai_num,
                    'price'           => $objGood->price,
                    'original_price'  => $objGood->original_price,
                    'total_price'     => $totalAmount,
                    'total_pay_price' => $orderAmount,
                    'content'         => $objGood->content,
                ];
                OrderGoodsModel::addOrderGoods($orderGoodsData);
            }
            //扣库存增加销量
            $editData = [
                'sale_num' => $objGood->sale_num + $num,
            ];
            if ($isStock == 1) {
                $editData['stock'] = $objGood->stock - $num;
                //todo::库存为0 是否下架
            }
            //更改库存
            GoodModel::edit($objGood, $editData);
            //写入记录
            OrderLogService::record(
                OrderLogModel::TYPE_USER,
                OrderLogModel::USER_ADD_ORDER,
                $orderId,
                $userId,
                OrderLogModel::USER_ADD_ORDER
            );
            //写入订单支付数据
            Db::commit();
//            $extra['transaction_id'] = '';
            //本地测试直接完成订单
            return [
                'order_id' => idToCode($orderId),
                'order_sn' => $orderData['order_sn'],
                'type'     => 'order'
            ];
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 订单列表
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @param int $payStatus
     * @return array|array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function orderList(int $userId, int $limit, int $offset, int $payStatus)
    {
        $result = ['list' => []];
        $param = ['user_id' => $userId];
        if ($payStatus > -1) {
            $param['pay_status'] = $payStatus;
        }
        if ($offset <= 0) {
            $result['query'] = OrderModel::countForOrder($param);
            if ($result['query'] <= 0) {
                return $result;
            }
        }
        $objOrder = OrderModel::listForOrder(
            $limit,
            $offset,
            $param,
            ['id' => 'desc'],
            ['orderGoods'],
            ['id', 'order_sn', 'pay_status', 'order_amount', 'create_time', 'num', 'order_status']
        );

        if ($objOrder->count() <= 0) {
            return $result;
        }

        foreach ($objOrder as $order) {
            $payStatus = $order->pay_status;
            if ($order->order_status == 0) {
                $payStatus = -1;
            }
            $result['list'][] = [
                'id'           => idToCode($order->id),
                'order_sn'     => $order->order_sn,
                'pay_status'   => $payStatus,
                'price'        => sprintf2($order->orderGoods->price / 100),
                'num'          => $order->num,
                'ai_num'       => $order->orderGoods->ai_num,
                'goods_name'   => $order->orderGoods->goods_name,
                'order_amount' => sprintf2($order->order_amount / 100)
            ];
        }
        return $result;
    }

    /**
     * 获取订单详情
     * @param int $id
     * @param int $userId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function orderDetails(int $id, int $userId)
    {
        $objOrder = OrderModel::findWithId($id);
        if (!$objOrder || $objOrder->user_id != $userId) {
            throw new ApiException('订单不存在');
        }
        if ($objOrder->order_status == 0) {
            throw new ApiException('订单已关闭');
        }
        $objGoodOrder = OrderGoodsModel::findByOrderId($objOrder->id);
        if (!$objGoodOrder) {
            throw new ApiException('订单信息错误');
        }
        $payStatus = $objOrder->pay_status;
        if ($objOrder->order_status == 0) {
            $payStatus = -1;
        }
        $orderGood = [
            'goods_id'       => $objGoodOrder->goods_id,
            'goods_name'     => $objGoodOrder->goods_name,
            'image'          => $objGoodOrder->image ?: '',
            'content'        => $objGoodOrder->content,
            'price'          => sprintf2($objGoodOrder->price / 100),
            'original_price' => sprintf2($objGoodOrder->original_price / 100),
            'ai_num'         => $objGoodOrder->ai_num,
        ];
        return [
            'id'           => $objOrder->id,
            'order_sn'     => $objOrder->order_sn,
            'pay_status'   => $payStatus,
            'pay_way'      => $objOrder->pay_way,
            'pay_time'     => $objOrder->pay_time,
            'num'          => $objOrder->num,
            'order_amount' => sprintf2($objOrder->order_amount / 100),
            'create_time'  => $objOrder->create_time,
            'goods_info'   => $orderGood
        ];
    }
}