<?php

declare (strict_types=1);

namespace app\services\pay;

use app\exceptions\ApiException;
use app\model\OrderModel;
use app\services\BaseService;
use app\services\wechat\WeChatPayServer;

/**
 * 支付统一入口
 * Class PayService
 * @package app\services\pay
 */
class PayService extends BaseService
{
    /**
     * 支付下单
     * @param int $userId
     * @param string $orderSn
     * @param int $payWay
     * @param string $orderSource
     * @return array|false|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function pay(int $userId, string $orderSn, int $payWay, string $orderSource = '1')
    {
        $objOrder = OrderModel::findWithOrderSn($orderSn);

        if (!$objOrder || $objOrder->user_id != $userId) {
            throw new ApiException('该支付订单未生成', 0);
        }
        if ($objOrder->pay_status != OrderModel::UNPAID) {
            throw new ApiException('支付交易订单不在待支付状态', 0);
        }
        if ($objOrder->order_status != OrderModel::ORDER_NORMAL) {
            throw new ApiException('支付交易订单已关闭状态', 0);
        }
        if ($objOrder->create_time + OrderModel::CANCELTIME < time()) {
            throw new ApiException('支付订单已超时，请重新下单', 0);
        }

        $from = 'order';
        $order = $objOrder->toArray();
        switch ($payWay) {
            case OrderModel::WECHAT_PAY:
                $res = WeChatPayServer::unifiedOrder($from, $order, $orderSource);
                break;
            case OrderModel::ALI_PAY:
                throw new ApiException('支付方式错误');
                break;
        }
        return $res;
    }

    /**
     * 轮询订单是否支付
     * @param string $orderSn
     * @param int $userId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function polling(string $orderSn, int $userId)
    {
        $result['pay_status'] = 2;
        $objOrder = OrderModel::findWithOrderSn($orderSn, true);
        if (!$objOrder || $objOrder->user_id != $userId) {
            return $result;
        }
        if ($objOrder->pay_status == 0) {
            $result['pay_status'] = 0;
        } elseif ($objOrder->pay_status == 1) {
            $result['pay_status'] = 1;
        }
        return $result;
    }
}
