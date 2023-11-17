<?php

namespace app\traits;


use App\Exceptions\ApiException;
use Firebase\JWT\JWT;
use think\facade\Env;

trait JwtAuthModelTrait
{
    /** @var string 路径 */
    protected static $path = '';
    /** @var string 加密密钥 */
    protected static $signatureKey = '';

    /** @var int 正常登陆的有效秒数 259200 72小时 */
    public static $normalLoginLiveSecond = 259200;

    /**
     * 获取线上生产环境的密钥
     * @param string $path
     * @return mixed|string
     * @author: LWW
     */
    private static function getSignatureKeyInProduction(string $path)
    {
        return env('JWT.JWT_KEY_' . $path) ?: '';
    }

    /**
     * 获取并设置密钥
     * @param string $path
     * @return mixed|string
     * @throws \Exception
     * @author: LWW
     */
    public static function setSignatureKey(string $path)
    {
        $path = strtoupper($path);
        self::$path = $path;
        $key = self::getSignatureKeyInProduction($path);
        if (empty($key)) {
            throw new ApiException('The JWT Key is empty!');
        }
        return self::$signatureKey = $key;
    }


    /**
     * 对一段内容(数组形式)进行jwt加密，并返回一个jwt(Json Web Token)
     * @param array $customerPayload
     * @param int $expireSeconds
     * @return string
     * @author LWW
     */
    public static function encrypt(array $customerPayload, int $expireSeconds = 0)
    {
        $host = app()->request->host();
        $time = time();
        /* 组合payload信息 */
        //签发人(不校验)
        $payload['iss'] = $host;
        //受众(不校验)
        $payload['aud'] = $host;
        //签发日期(会校验)
        $payload['iat'] = $time;
        //有效日期(会校验)
        $payload['exp'] = $time + (empty($expireSeconds) ? self::$normalLoginLiveSecond : $expireSeconds);
        //随机种子(不校验)
        $payload['sed'] = md5(uniqid(microtime(true), true));
        //自定义payload
        $payload['ctm'] = json_encode($customerPayload);

        return JWT::encode($payload, self::$signatureKey, 'HS256');
    }

    /**
     * 验证jwt是否正确
     * 根据加密方式，验证payload和签名部分是否匹配
     * @param string $token
     * @return object
     * @throws \Exception
     * @author LWW
     */
    public static function decrypt(string $token)
    {
        if (empty($token)) {
            throw new \Exception('token为空');
        }
        if (empty(self::$signatureKey)) {
            throw new \Exception('No set JWT Key!');
        }
        return JWT::decode($token, self::$signatureKey, ['HS256']);
    }

    /**
     * 以登录方式，验证和解析token的内容
     * @param string $token
     * @param string $path
     * @return mixed
     * @throws ApiException
     * @author: LWW
     */
    public static function decryptAndParseForLogin(string $token, string $path)
    {
        //验证jwt的签名
        try {
            $objToken = self::decrypt($token);
        } catch (\Exception $e) {
            throw new ApiException('请重新登陆', 1001);
        }
        //验证token中的环境变量与当前是否一致
        if (empty($objToken->rtm) || $objToken->rtm != env('APP_ENV')) {
            throw new ApiException('非法入境登陆', 1001);
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
        if (empty($customPayload['path']) || $customPayload['path'] != $path) {
            throw new ApiException('token来源不正确', 1001);
        }
        //是否有正确的uid信息
        if (empty($customPayload['uid'])) {
            throw new ApiException('无效登录信息', 1001);
        }
        //token剩余有效时间(秒)
        $customPayload['left_time'] = $objToken->exp - time();
        return $customPayload;
    }
}
