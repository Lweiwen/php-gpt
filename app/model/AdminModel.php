<?php

namespace app\model;

use think\model\concern\SoftDelete;

class AdminModel extends BaseModel
{
    use SoftDelete;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'admin';

    /**
     * 根据userId查找成员信息
     * @param int $userId
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function findWithUserId(int $userId)
    {
        return self::where('user_id', $userId)->find();
    }

    /**
     * 修改最后登录时间
     * @param AdminModel $obj
     * @return bool
     * @author LWW
     */
    public static function editLastLogin(self $obj)
    {
        $obj->last_login_time = time();
        return $obj->save();
    }
}