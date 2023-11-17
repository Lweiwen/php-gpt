<?php

namespace app\model;

use think\model\concern\SoftDelete;

class UserAuthModel extends BaseModel
{
    use SoftDelete;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'user_auth';

    /**
     * 根据用户ID查找数据
     * @param int $userId
     * @return UserAuthModel|array|mixed|\think\db\Query|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findByUserId(int $userId)
    {
        return self::where('user_id', '=', $userId)
//            ->where('client', '=', $client)
            ->find();
    }

    /**
     * 通过openid查找一个临时用户数据
     * @param string $openid
     * @return UserAuthModel|array|mixed|\think\db\Query|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findWithOpenid(string $openid)
    {
        return self::where('openid', $openid)->find();
    }

    /**
     * 新建数据
     * @param array $param
     * @return UserAuthModel|\think\Model
     * @author LWW
     */
    public static function addUniqueUser(array $param)
    {
        return self::create($param);
    }

    /**
     * 更新数据
     * @param UserAuthModel $obj
     * @param array $param
     * @return bool
     * @author LWW
     */
    public static function editUniqueUser(self $obj, array $param = [])
    {
        if (empty($param)){
            return true;
        }
        foreach ($param as $k => $val){
            $obj->$k = $val;
        }
        return $obj->save();
    }

    /**
     * 通过unionid查找一个临时用户数据
     * @param string $unionid
     * @return UserAuthModel|array|mixed|\think\db\Query|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findWithUnionId(string $unionid)
    {
        return self::where('unionid', $unionid)->find();
    }
}