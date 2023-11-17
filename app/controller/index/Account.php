<?php

namespace app\controller\index;

use app\BaseController;
use app\services\index\LoginService;
use app\services\index\UserService;
use app\validate\index\AccountValidata;

class Account extends BaseController
{
    /**
     * 手机密码注册
     * @return mixed
     * @throws \Throwable
     * @author LWW
     */
    public function register()
    {
        $post = $this->request->postMore(['mobile', 'password', 'repassword', 'client']);
        $this->validate(
            $post,
            AccountValidata::class,
            'register'
        );
        $result = (new UserService())->register($post);
        return $this->apiResponse($result);
    }

    /**
     * 获取登录codeUrl
     * @return mixed
     * @author LWW
     */
    public function codeUrl()
    {
        list($url) = $this->request->getMore(['url'], true);
        $result = (new LoginService())->codeUrl($url);
        return $this->apiResponse($result);
    }

}