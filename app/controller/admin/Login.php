<?php

namespace app\controller\admin;

use app\BaseController;
use app\services\auth\PasswordLoginService;
use app\validate\admin\AdminValidata;

class Login extends BaseController
{
    /**
     * 用户登录
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function login()
    {
        [$account, $password] = $this->request->postMore(
            [
                'mobile',
                'password',
            ],
            true
        );
        $this->validate(
            ['account' => $account, 'password' => $password],
            AdminValidata::class,
            'get'
        );
        $result = (new PasswordLoginService())->getAdminLoginToken($account, $password);
        return $this->apiResponse($result);
    }

}