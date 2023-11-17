<?php

namespace app\controller\index;

use app\BaseController;
use app\services\CacheService;
use app\services\index\LoginService;
use app\validate\index\AccountValidata;
use think\facade\App;

class Login extends BaseController
{
    /**
     * @var LoginService
     */
    protected $service;

    /**
     * 初始化
     */
    public function __construct(App $app, LoginService $services)
    {
        parent::__construct($app);
        $this->service = $services;
    }

    /**
     * 用户手机号密码登录
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function login()
    {
        [$mobile, $password] = $this->request->postMore(
            [
                ['mobile', ''],
                ['password', ''],
            ],
            true
        );
        $this->validate(
            ['mobile' => $mobile, 'password' => $password],
            AccountValidata::class,
            'login'
        );
        $result = $this->service->getUserMobileLogin($mobile, $password);
        return $this->apiResponse($result);
    }

    /**
     * 获取扫码登陆KEY+code（pc端）
     * @return mixed
     * @author LWW
     */
    public function getLoginKey()
    {
        $key = md5(time() . uniqid());
        $newUrl = env('APP_URL') . '/oauth/qrcode?sign_code=' . $key;
        $time = time() + 600;
        CacheService::set($key, 1, 600);
        return $this->apiResponse(['code' => $key, 'time' => $time, 'qrcode' => getQRCode($newUrl)]);
    }

    /**
     * 轮询是否登陆 （pc端）
     * @param string $key
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function scanLogin(string $key)
    {
        return $this->apiResponse($this->service->scanLogin($key));
    }

    /**
     * 设置扫描二维码状态-扫码
     * @param string $code
     * @return mixed
     * @author LWW
     */
    public function setLoginKey(string $code)
    {
        if (strlen($code) != 32 || !$code) {
            return $this->apiResponse('扫码失败请重新扫描', 0);
        }
        $cacheCode = CacheService::get($code);
        if ($cacheCode === false || $cacheCode === null) {
            return $this->apiResponse('二维码已过期请重新扫描', 0);
        }
        CacheService::set($code, '0', 600);
        return $this->apiResponse();
    }

    /**
     * 确认登录(确认pc端登录)
     * @param string $code
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function setLoginCode(string $code)
    {
        $userId = $this->request->uid();
        if (strlen($code) != 32 || !$code) {
            return $this->apiResponse('扫码失败请重新扫描', 0);
        }
        $this->service->setLoginCode($userId, $code);
        return $this->apiResponse();
    }
}