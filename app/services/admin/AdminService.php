<?php

namespace app\services\admin;

use app\exceptions\ApiException;
use app\model\AdminModel;
use app\model\UserModel;
use app\services\BaseService;

class AdminService extends BaseService
{
    /**
     * 返回管理员信息
     * @param int $userId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function adminInfo(int $userId)
    {
        $objUser = UserModel::findWithId($userId);
        $objAdmin = AdminModel::findWithUserId($userId);
        if (!$objAdmin) {
            throw new ApiException('管理员信息不能存在');
        }
        return [
            'user_id'    => $userId,
            'avatar'     => $objUser->avatar,
            'admin_role' => $objAdmin->role,
            'admin_name' => $objAdmin->name
        ];
    }
}