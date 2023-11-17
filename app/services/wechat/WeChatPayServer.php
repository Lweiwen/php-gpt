<?php

namespace app\services\wechat;

use app\exceptions\ApiException;
use app\model\OrderModel;
use app\model\TradeOrderModel;
use app\model\UserAuthModel;
use app\services\pay\PayNotifyService;
use EasyWeChat\Factory;
use think\Exception;
use think\facade\Env;
use think\facade\Log;

class WeChatPayServer
{

    protected static $error = '未知错误';
    protected static $return_code = 0;

    /**
     * 错误信息
     * @return string
     * @author LWW
     */
    public static function getError()
    {
        return self::$error;
    }

    /**
     * 返回状态码
     * @return int
     * @author LWW
     */
    public static function getReturnCode()
    {
        return self::$return_code;
    }


    /**
     * 微信统一下单
     * @param $from
     * @param $order
     * @param $orderSource
     * @return array|false|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author LWW
     */
    public static function unifiedOrder($from, $order, $orderSource)
    {
        try {
            $wechatConfig = self::getWeChatConfig($order, $orderSource);
            $openId = $wechatConfig['auth'];
            $config = $wechatConfig['config'];
            $notifyUrl = $wechatConfig['notify_url'];
            //jsapi需要验证openID
            if (!$openId && $orderSource == TradeOrderModel::jsapi) {
                throw new ApiException('用户授权信息失效');
            }
            $app = Factory::payment($config);
            $app->rebind('cache', cache());
            $attributes = self::getAttributes($from, $order, $orderSource, $openId, $notifyUrl);
            //查找是否有生成记录
            $objTraderOrder = TradeOrderModel::findWithOrderSnForLocking(
                $order['order_sn'],
                $attributes['out_trade_no']
            );
            if ($objTraderOrder) {
                $result = json_decode($objTraderOrder->prepay_params, true);
            } else {
                $result = $app->order->unify($attributes);
                //保存微信新增订单数据
                TradeOrderModel::add(
                    [
                        'order_type'    => 0,
                        'order_id'      => $order['id'],
                        'user_id'       => $order['user_id'],
                        'order_sn'      => $order['order_sn'],
                        'out_trade_no'  => $attributes['out_trade_no'],
                        'is_current'    => 1,
                        'total_amount'  => $order['order_amount'],
                        'pay_status'    => 0,
                        'prepay_id'     => $result['prepay_id'],
                        'prepay_params' => json_encode($result),
                    ]
                );
            }
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                //小程序,公众号
                if ($orderSource == TradeOrderModel::jsapi) {
                    $data = $app->jssdk->bridgeConfig($result['prepay_id'], false);
                }

                //pc端
                if ($orderSource == TradeOrderModel::native) {
                    //NATIVE 支付二维码
                    $data['qr_code'] = getQRCode($result['code_url']);
                }
                return $data;
            } else {
                if (isset($result['return_code']) && $result['return_code'] == 'FAIL') {
                    throw new ApiException($result['return_msg']);
                }
                if (isset($result['err_code_des'])) {
                    throw new ApiException($result['err_code_des']);
                }
                throw new ApiException('未知原因');
            }
        } catch (\Exception $e) {
            throw new ApiException('支付失败:' . $e->getMessage());
        }
    }

    /**
     * 支付参数
     * @param string $from
     * @param array $order
     * @param int $orderSource
     * @param string $openId
     * @param string $notifyUrl
     * @return array
     * @author LWW
     */
    public static function getAttributes(
        string $from,
        array $order,
        int $orderSource,
        string $openId,
        string $notifyUrl
    ) {
        switch ($from) {
            case 'order':
                $attributes = [
                    'trade_type' => 'JSAPI',
                    'body'       => '商品',
//                    'out_trade_no' => $order['order_sn'],
                    'total_fee'  => $order['order_amount'], // 单位：分
                    'notify_url' => $notifyUrl,
                    'openid'     => $openId,
                    'attach'     => 'order'
                ];
                break;
            case 'recharge':
                $attributes = [
                    'trade_type' => 'JSAPI',
                    'body'       => '充值',
//                    'out_trade_no' => $order['order_sn'],
                    'total_fee'  => $order['order_amount'], // 单位：分
                    'notify_url' => $notifyUrl,
                    'openid'     => $openId,
                    'attach'     => 'recharge'
                ];
                break;
        }

        //NATIVE模式设置
        if ($orderSource == TradeOrderModel::native) {
            $attributes['trade_type'] = 'NATIVE';
            $attributes['product_id'] = $order['order_sn'];
        }

        //在白名单内,一分钱
        if (self::isPayWhiteList($order['user_id'])) {
            $attributes['total_fee'] = 1;
        }

        //修改微信统一下单,订单编号 -> 支付回调时截取前面的单号 18个
        //修改原因:回调时使用了不同的回调地址,导致跨客户端支付时(例如小程序,公众号)可能出现201,商户订单号重复错误
        $attributes['out_trade_no'] = $order['order_sn'] . $attributes['trade_type'] . $orderSource;

        return $attributes;
    }


    /**
     * Notes: 获取微信配置
     * @param array $order
     * @param int $orderSource
     * @return array
     * @throws Exception
     * @author LWW
     */
    public static function getWeChatConfig(array $order, int $orderSource)
    {
        $payConfig = WeChatServer::getPayConfigBySource($orderSource);
        $auth = UserAuthModel::findByUserId($order['user_id']);
        $data = [
            'auth'         => $auth ? $auth->openid : '',
            'config'       => $payConfig['config'],
            'notify_url'   => $payConfig['notify_url'],
            'order_source' => $orderSource,
        ];
        return $data;
    }


    /**
     * Notes: 支付回调
     * @param $config
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     * @author LWW
     */
    public static function notify($config)
    {
        $wechatConfig = $config['config'];
        $app = Factory::payment($wechatConfig);
        $response = $app->handlePaidNotify(
            function ($message, $fail) {
                if ($message['return_code'] !== 'SUCCESS') {
                    return $fail('Order not exists.');
                }
                $objTradeOrder = TradeOrderModel::findWithOrderSnForLocking('', $message['out_trade_no']);
                if (!$objTradeOrder || $objTradeOrder->pay_status > TradeOrderModel::STATE_CREATE) {
                    return true;
                }

                // 用户是否支付成功
                if ($message['result_code'] === 'SUCCESS') {
                    $extra['transaction_id'] = $message['transaction_id'];
                    $attach = $message['attach'];

                    TradeOrderModel::edit(
                        $objTradeOrder,
                        [
                            'success_time' => time(),
                            'pay_status'   => TradeOrderModel::LOG_STATE_COMPLETE
                        ]
                    );
                    $orderSn = mb_substr($message['out_trade_no'], 0, 21);

                    $objOrder = OrderModel::findWithOrderSn($orderSn);
                    if (!$objOrder || $objOrder->pay_status >= OrderModel::ISPAID) {
                        return true;
                    }

                    PayNotifyService::handle('order', $orderSn, $extra);
                } elseif ($message['result_code'] === 'FAIL') {
                    // 用户支付失败
//                    TradeOrderModel::edit($objTradeOrder, ['pay_status' => TradeOrderModel::LOG_STATE_FAILURE]);

                    Log::record('支付失败' . json_encode($message), 'error');
                }
                return true; // 返回处理完成

            }
        );
        $response->send();
    }


    /**
     * Notes: 退款
     * @param $config
     * @param $data //微信订单号、商户退款单号、订单金额、退款金额、其他参数
     * @return array|bool|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @author LWW
     */
    public static function refund($config, $data)
    {
        if (!empty($data["transaction_id"])) {
            $app = Factory::payment($config);
            $result = $app->refund->byTransactionId(
                $data['transaction_id'],
                $data['refund_sn'],
                $data['total_fee'],
                $data['refund_fee']
            );
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 是否在白名单内支付
     * @param int $userId
     * @return bool
     * @author LWW
     */
    public static function isPayWhiteList(int $userId): bool
    {
        $whiteList = Env::get('wechat.white_list', '');
        $whiteList = explode(',', $whiteList);
        if (in_array($userId, $whiteList)) {
            return true;
        }
        return false;
    }
}