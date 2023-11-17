<?php

namespace app\services\wechat;

use app\model\Client_;
use app\model\TradeOrderModel;
use EasyWeChat\Factory;
use think\Exception;

class WeChatServer
{
    /**
     * 获取小程序配置
     * @return array
     */
    public static function getMnpConfig()
    {
        $config = [
            'app_id'        => config('wechat.mnp.app_id', ''),
            'secret'        => config('wechat.mnp.secret', ''),
            'mch_id'        => config('wechat.mnp.mch_id', ''),
            'key'           => config('wechat.mnp.key', ''),
            'response_type' => 'array',
            'log'           => [
                'level' => 'debug',
                'file'  => '../runtime/log/wechat.log'
            ],
        ];
        return $config;
    }

    /**
     * 获取微信公众号配置
     * @return array
     * @author LWW
     */
    public static function getOaConfig()
    {
        $config = [
            'app_id'        => config('wechat.oa.app_id', ''),
            'secret'        => config('wechat.oa.secret', ''),
            'mch_id'        => config('wechat.oa.mch_id', ''),
            'key'           => config('wechat.oa.key', ''),
            'token'         => config('wechat.oa.token', ''),
//            'response_type' => 'array',
            'log'           => [
                'level' => 'debug',
                'file'  => '../runtime/log/wechat.log'
            ],
        ];
        return $config;
    }

    /**
     * 获取微信开放平台应用配置
     * @return array
     * @author LWW
     */
    public static function getOpConfig()
    {
        $config = [
            'app_id'        => config('wechat.oa.app_id', ''),
            'secret'        => config('wechat.oa.secret', ''),
            'response_type' => 'array',
            'log'           => [
                'level' => 'debug',
                'file'  => '../runtime/log/wechat.log'
            ],
        ];
        return $config;
    }


    /**
     * 根据不同来源获取支付配置
     * @param int $orderSource
     * @return array
     * @throws Exception
     * @author LWW
     */
    public static function getPayConfigBySource(int $orderSource)
    {
//        $notify_url = '';
//        switch ($orderSource) {
//            case Client_::mnp:
//                $notify_url = '';
////                    url('payment/notifyMnp', [], '', true);
//                break;
//            case Client_::oa:
//            case Client_::pc:
//            case Client_::h5:
//                break;
//        }
        $notify_url = env('BACK_URL').'/web/pay/oa/notify';

        $config = self::getPayConfig($orderSource);
        if (empty($config) ||
            empty($config['key']) ||
            empty($config['mch_id']) ||
            empty($config['app_id']) ||
            empty($config['secret'])
        ) {
            throw new Exception('请在后台配置好微信支付');
        }

        return [
            'config'     => $config,
            'notify_url' => $notify_url,
        ];
    }

    //===================================支付配置=======================================================

    /**
     * 微信支付设置 H5支付 appid 可以是公众号appid
     * @param int $client
     * @return array
     * @author LWW
     */
    public static function getPayConfig(int $client)
    {

        switch ($client) {
            case Client_::mnp:                //小程序支付
                $appid = config('wechat.mnp.app_id', '');
                $secret = config('wechat.mnp.mnp', '');
                break;
            case TradeOrderModel::native:     //微信支付
            case TradeOrderModel::jsapi:
                $appid = config('wechat.oa.app_id', '');
                $secret = config('wechat.oa.secret', '');
                break;
            default:
                $appid = '';
                $secret = '';
        }

        $config = [
            'app_id'        => $appid,
            'secret'        => $secret,
            'mch_id'        => config('wechat.pay.mch_id', ''),
            'key'           => config('wechat.pay.pay_sign_key', ''),
            'cert_path'     => config('wechat.pay.apiclient_cert', ''),
            'key_path'      => config('wechat.pay.apiclient_key', ''),
            'response_type' => 'array',
            'log'           => [
                'level' => 'debug',
                'file'  => '../runtime/log/wechat.log'
            ],
        ];
//        if (is_cli()) {
//            $config['cert_path'] = ROOT_PATH . '/public/' . $pay['config']['apiclient_cert'];
//            $config['key_path'] = ROOT_PATH . '/public/' . $pay['config']['apiclient_key'];
//        }

        return $config;
    }

    public function index()
    {
        if (isset($_GET['echostr'])) {
            echo $_GET["echostr"];
            exit;
        }
        $config = $this->getOaConfig();
        $app = Factory::officialAccount($config);
        $response = $app->server->serve();
        $response->send();

    }
}