<?php

namespace app\controller\index;

use app\BaseController;
use app\services\index\UserService;

class User extends BaseController
{
    /**
     * 获取用户信息
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function info()
    {
        $result = (new UserService())->info();
        return $this->apiResponse($result);
    }

    /**
     * 修改用户信息
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function setInfo()
    {
        $post = [];
        if ($this->request->has('nickname')) {
            $post['nickname'] = $this->request->post('nickname', '');
        }
        if ($this->request->has('avatar')) {
            $post['avatar'] = $this->request->post('avatar', '');
        }
        $userId = $this->request->uid();
        if (!empty($post)) {
            (new UserService())->setInfo($userId, $post);
        }
        return $this->apiResponse();
    }
}