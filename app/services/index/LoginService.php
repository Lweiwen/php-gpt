<?php

namespace app\services\index;

use app\exceptions\ApiException;
use app\model\UserModel;
use app\services\auth\PasswordLoginService;
use app\services\BaseService;
use app\services\CacheService;
use app\services\wechat\WeChatServer;
use EasyWeChat\Factory;
use think\facade\Cache;

class LoginService extends BaseService
{
    /**
     * 手机账号密码登录
     * @param string $mobile
     * @param string $password
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function getUserMobileLogin(string $mobile, string $password)
    {
        $objUser = UserModel::findWithUserMobile($mobile);
        if (!$objUser) {
            throw new ApiException('账号错误');
        }
        if ($password && !password_verify($password, $objUser->password)) {
            throw new ApiException('账号或密码错误');
        }

        return (new PasswordLoginService())->getUserLoginToken(
            $objUser->id,
            $objUser->nickname,
            $objUser->avatar,
            $objUser->mobile
        );
    }

    /**
     * 扫码登陆
     * @param string $key
     * @return int[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function scanLogin(string $key): array
    {
        $hasKey = Cache::has($key);
        if ($hasKey === false) {
            $status = 0;//不存在需要刷新二维码
        } else {
            $keyValue = CacheService::get($key);
            if ($keyValue == 0) {
                $objUser = UserModel::findWithUniqid($key, ['mobile', 'id', 'nickname', 'avatar', 'uniqid']);
                if ($objUser) {
                    $tokenInfo = (new PasswordLoginService())->getUserLoginToken(
                        $objUser->id,
                        $objUser->nickname,
                        $objUser->avatar,
                        $objUser->mobile
                    );
                    $tokenInfo['status'] = 3;
                    UserModel::editUser($objUser, ['uniqid' => '']);
                    CacheService::delete($key);
                    return $tokenInfo;
                }
                $status = 1;//正在扫描中
            } else {
                $status = 2;//没有扫描
            }
        }
        return ['status' => $status];
    }

    /**
     * 确认登录
     * @param int $userId
     * @param string $code
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function setLoginCode(int $userId, string $code)
    {
        $cacheCode = CacheService::get($code);
        if ($cacheCode === false || $cacheCode === null) {
            throw new ApiException('二维码已过期请重新扫描');
        }
        $objUser = UserModel::findWithId($userId);
        if (!$objUser) {
            throw new ApiException('用户不存在');
        }
        UserModel::editUser($objUser, ['uniqid' => $code]);
        CacheService::set($code, '0', 600);
        return true;
    }

    /**
     * 获取code的url
     * @param $url
     * @return string
     */
    public function codeUrl($url)
    {
        $config = WeChatServer::getOaConfig();
        $app = Factory::officialAccount($config);
        return $app
            ->oauth
            ->scopes(['snsapi_userinfo'])
            ->redirect($url)
            ->getTargetUrl();
    }

//    public function wechatAuth()
//    {
//        $config = WeChatServer::getOaConfig();
//        $app = Factory::officialAccount($config);
//        $info = $app->oauth;
//    }

}