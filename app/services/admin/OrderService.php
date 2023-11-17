<?php

namespace app\services\admin;

use app\controller\index\User;
use app\exceptions\ApiException;
use app\model\OrderModel;
use app\model\UserModel;
use app\services\BaseService;

class OrderService extends BaseService
{
    /**
     * 订单列表
     * @param array $request
     * @param int $limit
     * @param int $offset
     * @return array|array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function list(array $request, int $limit, int $offset): array
    {
        if ($request['pay_status'] < -0) {
            unset($request['pay_status']);
        } elseif ($request['pay_status'] == 3) {
            unset($request['pay_status']);
            $request['order_status'] = 0;
        } else {
            $request['order_status'] = 1;
        }
        $result = ['list' => []];
        if ($offset <= 0) {
            $result['query'] = OrderModel::countForOrder($request);
            if ($result['query'] <= 0) {
                return $result;
            }
        }
        $objOrder = OrderModel::listForOrder($limit, $offset, $request, ['id' => 'desc']);
        if ($objOrder->count() <= 0) {
            return $result;
        }
        $objUser = UserModel::listByUserIds(array_unique($objOrder->column('user_id')), ['id', 'nickname']);
        $userInfo = [];
        if ($objUser->count()) {
            $userInfo = array_column($objUser->toArray(), null, 'id');
        }
        $objUser = null;

        //支付状态;
        //0-待支付;1-已支付;2-已退款;3-已关闭
        foreach ($objOrder as $order) {
            $nickname = '';
            if (isset($userInfo[$order->user_id])) {
                $nickname = $userInfo[$order->user_id]['nickname'];
            }
            $payStatus = $order->pay_status;
            if ($order->order_status = 0) {
                $payStatus = 3;
            }
            $result['list'][] = [
                'id'           => $order->id,
                'order_sn'     => $order->order_sn,
                'nickname'     => $nickname,
                'pay_status'   => $payStatus,
                'pay_way'      => $order->pay_way,
                'order_amount' => sprintf2($order->order_amount / 100),
                'create_time'  => $order->create_time
            ];
        }
        return $result;
    }

    /**
     * 订单详情
     * @param int $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function details(int $id): array
    {
        $objOrder = OrderModel::findWithId($id);
        if (!$objOrder) {
            throw new ApiException('订单不存在');
        }
        $orderGoods = $objOrder->orderGoods;
        $objUser = UserModel::findWithId($objOrder->user_id);
        $payStatus = $objOrder->pay_status;
        if ($objOrder->order_status = 0) {
            $payStatus = 3;
        }
        return [
            'id'             => $objOrder->id,
            'order_sn'       => $objOrder->order_sn,
            'pay_status'     => $payStatus,
            'pay_way'        => $objOrder->pay_way,
            'pay_time'       => $objOrder->pay_time ?: 0,
            'transaction_id' => $objOrder->transaction_id,
            'num'            => $objOrder->num,
            'order_amount'   => sprintf2($objOrder->order_amount / 100),
            'create_time'    => $objOrder->create_time,
            'goods_info'     => [
                'id'             => $orderGoods->goods_id,
                'goods_name'     => $orderGoods->goods_name,
                'price'          => sprintf2($orderGoods->price / 100),
                'original_price' => sprintf2($orderGoods->original_price / 100),
                'ai_num'         => $orderGoods->ai_num,
            ],
            'user_info'      => [
                'id'       => $objUser->id,
                'nickname' => $objUser->nickname ?: '',
                'mobile'   => $objUser->mobile ?: '',
                'avatar'   => $objUser->avatar ?: '',
            ],
        ];
    }
}