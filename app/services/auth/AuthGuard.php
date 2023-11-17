<?php

namespace app\services\auth;

use app\exceptions\ApiException;
use app\model\AdminModel;
use app\model\UserModel;
use app\Request;
use app\services\auth\AuthService;
use app\services\BaseService;

class AuthGuard extends BaseService
{
    /**
     * @var array
     */
    protected $token = '';

    /**
     * 登录认证路径
     * @var string
     */
    protected $authPath = '';

    /**
     * 当前登录用户信息获取的对象
     */
    protected $user;

    /**
     * AuthGuard constructor.
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * 检查和设置登录认证路径
     * @param string $authPath
     * @param array $arrAuthPath
     * @return bool
     * @author LWW
     */
    public function checkAndSetAuthPath(string $authPath, array $arrAuthPath = [])
    {
        //如果传入为空，则使用autoSetAuthPath方法来进行检查和设置
        if (empty($authPath)) {
            return $this->autoSetAuthPath($arrAuthPath);
        } else {
            $this->authPath = $authPath;
        }
        return true;
    }

    /**
     * 通过解析jwt(token)，获取登录认证路径
     * @return bool
     */
    public function autoSetAuthPath(array $arrAuthPath)
    {
        /** 截取jwt载体内容，并解码 */
        $arrJwt = explode('.', $this->token);
        if (count($arrJwt) != 3 || empty($arrJwt[1])) {
            return false;
        }
        $payload = base64_decode($arrJwt[1], true);
        if ($payload) {
            $payload = json_decode($payload, true);
        }
        /** 如果能从payload获取有效的认证登录信息(path)，则设置，并返回true  */
        if (is_array($payload) && isset($payload['ctm'])) {
            $payload = json_decode($payload['ctm'], true);
        }
        if (
            is_array($payload)
            && isset($payload['path'])
            && (empty($arrAuthPath) || in_array($payload['path'], $arrAuthPath))
        ) {
            $this->authPath = $payload['path'];
            return true;
        }
        return false;
    }

    /**
     * 检查用户是否通过了验证
     * @return bool
     * @throws \Exception
     */
    public function check()
    {
        return $this->user();
    }

    /**
     * 检查登录token，并获取当前登录用户信息
     * 本方法是\Illuminate\Contracts\Auth\Guard接口指定实现方法
     * 说明：
     * 1、返回的信息是一个实例对象，该实例对象要实现接口\Illuminate\Contracts\Auth\Authenticatable
     *
     *
     * @throws \Exception
     */
    public function user()
    {
        //检查header头、token、登录认证路径
        if (empty($this->token)) {
            return false;
        }
        //是否已经验证登录
//        if (!is_null($this->user)) {
//            return $this->user;
//        }
        //根据登录认证路径，设置密钥
        AuthService::setSignatureKey($this->authPath);
        //以登录方式，验证和解析token的内容
        $tokenData = AuthService::decryptAndParseForLogin(
            $this->token
        );
        if (empty($tokenData)) {
            return false;
        }
        //临时登录
        if ($this->authPath == 'silence') {
            return false;
        }
        $uid = $tokenData['uid'];
        $obj = UserModel::findWithId($uid, ['id', 'nickname', 'mobile', 'real_name', 'avatar', 'status']);
        if (!$obj) {
            return false;
        }
        if ($obj->status == 0) {
            throw new ApiException('账号已停用', 1001);
        }

        $userInfo = $obj->toArray();
        // 验证后台信息
        if ($this->authPath == 'admin') {
            $objMember = AdminModel::findWithUserId($uid);
            if (!$objMember) {
                return false;
            }
            $adminInfo = [
                'id'   => $objMember->id,
                'name' => $objMember->name,
                'role' => $objMember->role
            ];
            Request::macro(
                'isAdminLogin',
                function () use (&$adminInfo) {
                    return !is_null($adminInfo);
                }
            );
            Request::macro(
                'adminInfo',
                function () use (&$adminInfo) {
                    return $adminInfo;
                }
            );
        }
        //设置用户ID
        Request::macro(
            'uid',
            function () use (&$uid) {
                return $uid;
            }
        );
        //设置用户信息
        Request::macro(
            'user',
            function (string $key = null) use (&$userInfo) {
                if ($key) {
                    return $userInfo[$key] ?? '';
                }
                return $userInfo;
            }
        );
        //设置token信息
        Request::macro(
            'tokenData',
            function () use (&$tokenData) {
                return $tokenData;
            }
        );
        return true;
    }
}