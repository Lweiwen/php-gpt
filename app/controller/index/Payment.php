<?php

namespace app\controller\index;

use app\BaseController;
use app\model\TradeOrderModel;
use app\services\pay\PayService;
use app\services\wechat\WeChatPayServer;
use app\services\wechat\WeChatServer;

class Payment extends BaseController
{
    /**
     * 支付
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function pay()
    {
        list($orderSn, $payWay, $tradeType) = $this->request->postMore(
            [
                ['order_sn', ''],
                ['pay_way', 1],
                ['trade_type', 1]
            ],
            true
        );
        if (empty($orderSn)) {
            return $this->apiResponse('业务订单不能为空', 0);
        }
        $userId = $this->request->uid();
        $res = (new PayService())->pay($userId, $orderSn, $payWay, $tradeType);
        return $this->apiResponse($res);
    }

    /**
     * 轮询订单是否实付
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function polling()
    {
        $orderSn = $this->request->post('order_sn');
        if (empty($orderSn)) {
            return $this->apiResponse('业务订单不能为空', 0);
        }
        $userId = $this->request->uid();
        return $this->apiResponse((new PayService())->polling($orderSn, $userId));
    }

    /**
     * 公众号支付回调
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     * @throws \think\Exception
     * @author LWW
     */
    public function notifyOa()
    {
        $config = WeChatServer::getPayConfigBySource(TradeOrderModel::native);
        WeChatPayServer::notify($config);
    }

}