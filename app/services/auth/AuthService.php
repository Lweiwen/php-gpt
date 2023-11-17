<?php

namespace app\services\auth;

use app\exceptions\ApiException;
use app\services\BaseService;
use app\traits\JwtAuthModelTrait;

class AuthService extends BaseService
{
    use JwtAuthModelTrait;

    /**
     * 以登录方式，验证和解析token的内容
     * @param string $token
     * @param string $path
     * @return mixed
     * @throws \Exception
     * @author LWW
     */
    public static function decryptAndParseForLogin(string $token)
    {
        $path = self::$path;
        try {
            $objToken = self::decrypt($token);
        } catch (\Exception $e) {
            throw new ApiException('请重新登陆', 1001);
        }
        //验证token的签发时间有无大于当前时间
        if ($objToken->iat > time()) {
            throw new ApiException('无效token', 1001);
        }
        //验证token是否已经过期
        if ($objToken->exp < time()) {
            throw new ApiException('登录信息过期', 1001);
        }
        /** 获取并校验登录信息内容 */
        $customPayload = json_decode($objToken->ctm, true);
        //检验登录认证来源与传入的是否一致
        if (empty($customPayload['path']) || strtoupper($customPayload['path']) != $path) {
            throw new ApiException('token来源不正确', 1001);
        }
        //是否有正确的uid信息
        if (empty($customPayload['uid'])) {
            throw new ApiException('无效登录信息', 1001);
        }
        return $customPayload;
    }

}