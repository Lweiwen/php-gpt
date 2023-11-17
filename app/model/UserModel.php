<?php

namespace app\model;

use think\model\concern\SoftDelete;

class UserModel extends BaseModel
{
    use SoftDelete;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'user';

    /**
     * 根据账号查找数据
     * @param string $account
     * @return UserModel|array|mixed|\think\db\Query|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findWithUserAccount(string $account)
    {
        return self::where('account', $account)
            ->where('status', 1)
            ->find();
    }

    /**
     * 根据账号查找用户
     * @param string $mobile
     * @return UserModel|array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findWithUserMobile(string $mobile)
    {
        return self::where('mobile', $mobile)
            ->where('status', 1)
            ->find();
    }

    /**
     * 根据用户id获取数据
     * @param string $id
     * @param array $field
     * @return UserModel|array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findWithId(string $id, array $field = ['*'])
    {
        return self::where('id', $id)
            ->where('status', 1)
            ->field($field)
            ->find();
    }

    /**
     * 根据手机号获取数据
     * @param string $mobile
     * @return UserModel|array|mixed|\think\db\Query|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findWithMobile(string $mobile)
    {
        return self::where('mobile', $mobile)
            ->find();
    }

    /**
     * 新增用户
     * @param $data
     * @return int|string
     * @author LWW
     */
    public static function addUser($data)
    {
        return self::insertGetId($data);
    }

    /**
     * 更具uniqid 获取用户信息
     * @param string $uniqid
     * @param array $field
     * @return UserModel|array|mixed|\think\db\Query|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findWithUniqid(string $uniqid, array $field)
    {
        return self::where('uniqid', '=', $uniqid)->field($field)->find();
    }

    /**
     * 更新用户信息
     * @param UserModel $obj
     * @param array $param
     * @return bool
     * @author LWW
     */
    public static function editUser(self $obj, array $param): bool
    {
        foreach ($param as $key => $val) {
            $obj->$key = $val;
        }
        return $obj->save();
    }

    /**
     * 更具用户ID获取数据
     * @param array $userIds
     * @param array|string[] $field
     * @return UserModel[]|array|\think\Collection|\think\model\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function listByUserIds(array $userIds, array $field = ['*'])
    {
        return self::whereIn('id', $userIds)->field($field)->select();
    }

    /**
     * 新增用户数量
     * @param string $time
     * @return int|\think\db\Query
     * @throws \think\db\exception\DbException
     * @author LWW
     */
    public static function todayAddUser(string $time)
    {
        return self::whereTime('create_time', $time)
            ->count();
    }

    /**
     * 处理筛选条件
     * @param array $param
     * @return UserModel|\think\db\Query
     * @author LWW
     */
    private static function dealForSelect(array $param)
    {
        $where = [];
        if (isset($param['begin_time']) && !empty($param['begin_time'])) {
            $where[] = ['create_time', '>=', $param['begin_time']];
        }
        if (isset($param['end_time']) && !empty($param['end_time'])) {
            $where[] = ['create_time', '<', $param['end_time']];
        }
        if (isset($param['keywords']) && !empty($param['keywords'])) {
            $where[] = ['nickname|mobile', 'like', '%' . $param['keywords'] . '%'];
        }
        if (isset($param['user_id']) && !empty($param['user_id'])) {
            $where[] = ['id', '=', $param['user_id']];
        }
        return self::where($where);
    }

    /**
     * 查询数据
     * @param array $param
     * @param int $limit
     * @param int $offset
     * @return UserModel[]|array|\think\Collection|\think\db\Query[]|\think\model\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function listForUser(array $param, int $limit, int $offset)
    {
        $obj = self::dealForSelect($param);
        if ($limit > 0) {
            $obj->limit($offset, $limit);
        }
        return $obj->select();
    }

    /**
     * 统计数量
     * @param array $param
     * @return int|\think\db\Query
     * @throws \think\db\exception\DbException
     * @author LWW
     */
    public static function countForUser(array $param)
    {
        return self::dealForSelect($param)->count();
    }
}