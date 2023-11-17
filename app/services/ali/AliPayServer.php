<?php


namespace app\services\ali;


use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Config;
use app\model\Client_;
use app\model\OrderModel;
use app\services\pay\PayNotifyService;
use think\facade\Log;

class AliPayServer
{


    protected $error = '未知错误';

    public function getError()
    {
        return $this->error;
    }


    public function __construct()
    {
        Factory::setOptions($this->getOptions());
    }


    /**
     * Notes: 支付设置
     * @return Config
     * @throws \Exception
     * @author LWW
     */
    public function getOptions()
    {
//        $result = (new Pay())->where(['code' => 'alipay'])->find();
        $result = [];
        if (empty($result)) {
            throw new \Exception('请配置好支付设置');
        }

        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
//        $options->gatewayHost = 'openapi.alipaydev.com'; //测试沙箱地址
        $options->signType = 'RSA2';
        $options->appId = $result['config']['app_id'] ?? '';
        // 应用私钥
        $options->merchantPrivateKey = $result['config']['private_key'] ?? '';
        //支付宝公钥
        $options->alipayPublicKey = $result['config']['ali_public_key'] ?? '';
        //回调地址
        $options->notifyUrl = url('payment/aliNotify', '', '', true);
        return $options;
    }


    /**
     * Notes: pc支付
     * @param $attach
     * @param $order
     * @return string
     * @throws \Exception
     * @author LWW
     */
    public function pagePay($attach, $order)
    {
        $domain = request()->domain();
        $result = Factory::payment()->page()->optional('passback_params', $attach)->pay(
            '订单:' . $order['order_sn'],
            $order['order_sn'],
            $order['order_amount'],
            $domain . '/pc/user/order'
        );
        return $result->body;
    }


    /**
     * Notes: app支付
     * @param $attach
     * @param $order
     * @return string
     * @throws \Exception
     * @author LWW
     */
    public function appPay($attach, $order)
    {
        $result = Factory::payment()->app()->optional('passback_params', $attach)->pay(
            $order['order_sn'],
            $order['order_sn'],
            $order['order_amount']
        );
        return $result->body;
    }


    /**
     * Notes: 手机网页支付
     * @param $attach
     * @param $order
     * @return string
     * @throws \Exception
     * @author LWW
     */
    public function wapPay($attach, $order)
    {
        $domain = request()->domain();
        $result = Factory::payment()->wap()->optional('passback_params', $attach)->pay(
            '订单:' . $order['order_sn'],
            $order['order_sn'],
            $order['order_amount'],
            $domain . '/mobile/pages/user_order/user_order',
            $domain . '/mobile/pages/user_order/user_order'
        );
        return $result->body;
    }


    /**
     * Notes: 支付
     * @param $from
     * @param $order
     * @param $order_source
     * @return bool|string
     * @author LWW
     */
    public function pay($from, $order, $order_source)
    {
        try {
            switch ($order_source) {
                case Client_::pc:
                    $result = $this->pagePay($from, $order);
                    break;
                case Client_::h5:
                    $result = $this->wapPay($from, $order);
                    break;
                default:
                    throw new \Exception('支付方式错误');
            }
            return $result;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }


    /**
     * 支付回调验证
     * @param $data
     * @return bool
     * @author LWW
     */
    public function verifyNotify($data)
    {
        try {
            $verify = Factory::payment()->common()->verifyNotify($data);
            if (false === $verify) {
                throw new \Exception('异步通知验签失败');
            }
            if (!in_array($data['trade_status'], ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
                return true;
            }
            $extra['transaction_id'] = $data['trade_no'];

            //验证订单是否已支付
            $objOrder = OrderModel::findWithOrderSn($data['out_trade_no']);
            if (!$objOrder || $objOrder->pay_status >= OrderModel::ISPAID) {
                return true;
            }
            PayNotifyService::handle('order', $data['out_trade_no'], $extra);

            return true;
        } catch (\Exception $e) {
            $record = [
                __CLASS__,
                __FUNCTION__,
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            ];
            Log::record(implode('-', $record));
            return false;
        }
    }


    /**
     * 查询订单
     * @param $order_sn
     * @return \Alipay\EasySDK\Payment\Common\Models\AlipayTradeQueryResponse
     * @throws \Exception
     * @author LWW
     */
    public function checkPay($order_sn)
    {
        return Factory::payment()->common()->query($order_sn);
    }


    /**
     * 退款
     * @param $orderSn  [订单号]
     * @param $orderAmount [金额]
     * @return \Alipay\EasySDK\Payment\Common\Models\AlipayTradeRefundResponse
     * @throws \Exception
     * @author LWW
     */
    public function refund($orderSn, $orderAmount)
    {
        return Factory::payment()->common()->refund($orderSn, $orderAmount);
    }


}

