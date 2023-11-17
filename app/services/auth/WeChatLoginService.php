<?php

namespace app\services\auth;

use app\model\Client_;
use app\model\UserModel;
use app\services\BaseService;
use app\traits\JwtAuthModelTrait;

class WeChatLoginService extends BaseService
{
    use JwtAuthModelTrait;

    /**
     * 手机端用户id登录
     * （一般是微信授权登录使用）
     * @param int $userId
     * @param string $client
     * @return null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function getWebLoginTokenByUserId(int $userId, string $client = 'h5'): ?array
    {
        $objUser = UserModel::findWithId($userId);
        if (!$objUser) {
            return null;
        }
        self::setSignatureKey('web');
        $token = self::encrypt(
            [
                'client' => $client,
                'uid'    => $userId,
                'path'   => 'web'
            ]
        );
        return [
            'token'    => $token,
            'userInfo' => [
                'id'       => idToCode($userId),
                'avatar'   => $objUser->avatar,
                'nickname' => $objUser->nickname,
                'mobile'   => $objUser->mobile,
            ],
        ];
    }

    /**
     * 获取手机端用户沉默登陆token
     * @param string $openid
     * @return string
     * @throws \Exception
     * @author LWW
     */
    public function getSilenceWebLoginToken(string $openid): string
    {
        self::setSignatureKey('silence');
        //返回token和用户权限名称
        return self::encrypt(
            [
                'uid'    => idToCode(0),
                'openid' => $openid,
                'path'   => 'silence'
            ]
        );
    }

}