<?php

namespace app\controller\index;

use app\BaseController;
use app\model\UserAIMoneyChangeModel;
use app\services\auth\WeChatLoginService;
use app\services\index\UserAiMoneyService;
use app\services\wechat\WeChatServer;
use app\services\wechat\WxUserService;
use EasyWeChat\Factory;
use think\facade\Db;
use think\facade\Log;

class Wechat extends BaseController
{
    /**
     * 获取jssdk配置
     * @return bool|mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @author LWW
     */
    public function jsConfig()
    {
        //检查referer地址
        if (!$this->checkRefererUrl($this->request->header('Referer') ?? '')) {
            return $this->apiResponse('origin url error', 0);
        }

        $url = $this->request->post('url');
        if (!$this->checkTargetUrl($url)) {
            return $this->apiResponse('url error', 0);
        }
        $config = WeChatServer::getOaConfig();
        $wxApp = Factory::officialAccount($config);
        $wxApp->rebind('cache', cache());
        $ticket = $wxApp->jssdk->getTicket()['ticket'];
        $nonce = \EasyWeChat\Kernel\Support\Str::quickRandom(10);
        $timestamp = time();
        $signature = $wxApp->jssdk->getTicketSignature($ticket, $nonce, $timestamp, $url);
        return $this->apiResponse(
            [
                'app_id'    => $wxApp->getConfig()['app_id'],
                'nonce_str' => $nonce,
                'timestamp' => $timestamp,
                'url'       => $url,
                'signature' => $signature,
            ]
        );
    }

    /**
     * 显示授权(公众号授权登录)
     * @return mixed
     * @author LWW
     */
    public function wxUserInfoLoginUrl()
    {
        //检查referer地址
        if (!$this->checkRefererUrl($this->request->header('Referer') ?? '')) {
            return $this->apiResponse('origin url error', 0);
        }

        $targetUrl = $this->request->post('target_url', '');
        if (!$this->checkTargetUrl($targetUrl)) {
            return $this->apiResponse('url error', 0);
        }

        $randStr = $this->request->post('rdc', '');
        if (empty($randStr) || !preg_match('/^[a-zA-Z0-9]{16,24}$/', $randStr)) {
            return $this->apiResponse('rand string error', 0);
        }
        //去除目标url敏感参数
        $targetUrl = $this->delQueryToUrl($targetUrl, ['tp', 'code', 'state', 'rdc']);
        //添加指定参数到目标url
        $targetUrl = $this->addQueryToUrl($targetUrl, ['tp' => 2]);
        $config = WeChatServer::getOaConfig();
        $wxApp = Factory::officialAccount($config);
        $wxApp->rebind('cache', cache());
        return $this->apiResponse(
            [
                'url' => $wxApp
                    ->oauth
                    ->scopes(['snsapi_userinfo'])
                    ->withState($randStr)
                    ->redirect($targetUrl)
            ]
        );
    }

    /**
     * 解析显式授权code
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException|\Throwable
     * @author LWW
     */
    public function wxUserInfoLoginCodeDecode()
    {
        if (!$this->checkRefererUrl($this->request->header('Referer') ?? '')) {
            return $this->apiResponse('origin url error', 0);
        }
        $wxCode = $this->request->post('wx_code');
        if (empty($wxCode)) {
            return $this->apiResponse('invalid code', 0);
        }
        //请求微信解析显式授权的code
        try {
            $config = WeChatServer::getOaConfig();
            $wxApp = Factory::officialAccount($config);
            $wxApp->rebind('cache', cache());

            $wxUser = $wxApp
                ->oauth
                ->scopes(['snsapi_userinfo'])
                ->userFromCode($wxCode);
        } catch (\Throwable $e) {
            return $this->apiResponse('登录失败，' . $e->getCode() . '，|||' . $e->getMessage(), 0);
        }
        $arrWxUser = $wxUser->getAttributes()['raw'];
        Log::record(json_encode($arrWxUser), 'error');

        if (empty($arrWxUser['openid'])) {
            return $this->apiResponse('wx_code can not get user info', 0);
        }
        Db::startTrans();
        try {
            $repoWxUser = new WxUserService();
            $objU = $repoWxUser->createUserAuthByOpenid($arrWxUser['openid'], $arrWxUser['unionid'] ?? '');
            $userId = $objU->user_id;

            if ($userId == 0) {
                $userId = $repoWxUser->createUser(
                    $objU,
                    [
                        'headimgurl' => $arrWxUser['headimgurl'],
                        'nickname'   => $arrWxUser['nickname']
                    ]
                );
                (new UserAiMoneyService())->chatAiMoney($userId, 3, UserAIMoneyChangeModel::default_add);
            }

            $arrAuRes = (new WeChatLoginService())->getWebLoginTokenByUserId($userId);
            if (empty($arrAuRes)) {
                return $this->apiResponse('登录失败，请稍后重试', 0);
            }
            $arrAuRes['openid'] = $arrWxUser['openid'];
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
        return $this->apiResponse($arrAuRes);
    }

    /**
     * 获取静默授权地址
     * @return mixed
     * @author LWW
     */
    public function wxBaseLoginUrl()
    {
        //检查referer地址
        if (!$this->checkRefererUrl($this->request->header('Referer') ?? '')) {
            return $this->apiResponse('origin url error', 0);
        }

        $targetUrl = $this->request->post('target_url');
        if (!$this->checkTargetUrl($targetUrl)) {
            return $this->apiResponse('url error', 0);
        }
        $randStr = $this->request->post('rdc');
        if (empty($randStr) || !preg_match('/^[a-zA-Z0-9]{16,24}$/', $randStr)) {
            return $this->apiResponse('rand string error', 0);
        }
        $tp = $this->request->post('tp', 1);
        if (is_numeric($tp) && $tp == 3) {
            $tp = intval($tp);
        } else {
            $tp = 1;
        }
        $targetUrl = $this->addQueryToUrl($targetUrl, ['tp' => $tp]);
        $config = WeChatServer::getOaConfig();
        $wxApp = Factory::officialAccount($config);
        $wxApp->rebind('cache', cache());
        return $this->apiResponse(
            [
                'url' => $wxApp
                    ->oauth
                    ->scopes(['snsapi_base'])
                    ->withState($randStr)
                    ->redirect($targetUrl)
            ]
        );
    }

    /**
     * 解析静默授权code
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function wxBaseLoginCodeDecode()
    {
        if (!$this->checkRefererUrl($this->request->header('Referer') ?? '')) {
            return $this->apiResponse('origin url error', 0);
        }
        $wxCode = $this->request->post('wx_code');
        if (empty($wxCode)) {
            return $this->apiResponse('invalid code', 0);
        }
        //沉默登陆标识
        $silence = !empty($this->request->post('silence', 0));
        try {
            $config = WeChatServer::getOaConfig();
            $wxApp = Factory::officialAccount($config);
            $wxApp->rebind('cache', cache());
            $wxUser = $wxApp
                ->oauth
                ->scopes(['snsapi_base'])
                ->userFromCode($wxCode);
        } catch (\Throwable $e) {
            return $this->apiResponse('登录失败，' . $e->getCode() . '，|||' . $e->getMessage(), 0);
        }
        $openid = $wxUser->getAttribute('id');
        if (empty($openid)) {
            return $this->apiResponse('wx_code can not get openid', 0);
        }
        $objU = (new WxUserService())->createUserAuthByOpenid($openid);
        if ($objU->user_id > 0) {
            $result = (new WeChatLoginService())->getWebLoginTokenByUserId($objU->user_id);
        } elseif ($silence && $objU->user_id <= 0) {
            $result = [
                'silence_token' => (new WeChatLoginService())->getSilenceWebLoginToken($objU->openid)
            ];
        }
        $result['openid'] = $objU->openid;
        return $this->apiResponse($result);
    }

    /**
     * 封装检查域名地址
     * @param string $url
     * @return bool
     * @author: LWW
     */
    private function checkLjhDomain(string $url)
    {
        //暂时性开放授权相关接口的来源url检测
        return true;
        if (empty($url) || !preg_match('/^http(s)?:\/\/([a-zA-Z0-9]+\.)?ljhui((\.net$)|(\.net[\/\?#]))/', $url)) {
            return false;
        }
        return true;
    }

    /**
     * 封装检查目标地址
     * @param string $referer
     * @return bool
     * @author LWW
     */
    private function checkTargetUrl(string $referer = '')
    {
        return $this->checkLjhDomain($referer);
    }

    /**
     * 封装检查referer地址
     * @param string $referer
     * @return bool
     * @author LWW
     */
    private function checkRefererUrl(string $referer)
    {
        return $this->checkLjhDomain($referer);
    }

    /**
     * 去掉url其他字符
     * @param string $url
     * @param array $delQueryNames
     * @return string
     * @author LWW
     */
    private function delQueryToUrl(string $url, array $delQueryNames = [])
    {
        if (empty($delQueryNames)) {
            return $url;
        }
        $pu = parse_url($url);
        if (empty($pu['query'])) {
            return $url;
        }
        $qy = [];
        parse_str($pu['query'], $qy);
        foreach ($delQueryNames as $v) {
            $v = strval($v);
            if (isset($qy[$v])) {
                unset($qy[$v]);
            }
        }
        if (!empty($qy)) {
            ksort($qy);
            $pu['query'] = http_build_query($qy);
        }

        return $pu['scheme'] . '://' . $pu['host']
            . (empty($pu['port']) ? '' : ':' . $pu['port'])
            . ($pu['path'] ?? '')
            . (empty($pu['query']) ? '' : '?' . $pu['query'])
            . (empty($pu['fragment']) ? '' : '#' . $pu['fragment']);
    }

    /**
     * url添加参数
     * @param string $url
     * @param array $addQuery
     * @return string
     * @author LWW
     */
    private function addQueryToUrl(string $url, array $addQuery = [])
    {
        if (empty($addQuery)) {
            return $url;
        }
        foreach ($addQuery as $k => $v) {
            $addQuery[$k] = strval($v);
        }
        $pu = parse_url($url);
        $qy = [];
        if (!empty($pu['query'])) {
            parse_str($pu['query'], $qy);
        }
        $qy = array_merge($qy, $addQuery);
        ksort($qy);
        $pu['query'] = http_build_query($qy);

        return $pu['scheme'] . '://' . $pu['host']
            . (empty($pu['port']) ? '' : ':' . $pu['port'])
            . ($pu['path'] ?? '')
            . (empty($pu['query']) ? '' : '?' . $pu['query'])
            . (empty($pu['fragment']) ? '' : '#' . $pu['fragment']);
    }
}