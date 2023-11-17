<?php
namespace app\controller\admin;

use app\BaseController;
use app\services\admin\AdminService;

class Admin extends BaseController
{
    /**
     * 管理员信息
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function adminInfo()
    {
        $uId = $this->request->uid();
        return $this->apiResponse((new AdminService())->adminInfo($uId));
    }
}