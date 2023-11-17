<?php

namespace app\model;

use think\model\concern\SoftDelete;

class UserAiMoneyModel extends BaseModel
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'user_ai_money';
    use SoftDelete;

    /**
     * 更加用户ID查找数据
     * @param int $userId
     * @return UserAiMoneyModel|array|mixed|\think\db\Query|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findWithUserId(int $userId)
    {
        return self::where('user_id', $userId)->find();
    }

    /**
     * 更新数据
     * @param UserAiMoneyModel $obj
     * @param array $param
     * @return bool
     * @author LWW
     */
    public static function editAiMoney(self $obj, array $param)
    {
        foreach ($param as $key => $val) {
            $obj->$key = $val;
        }
        return $obj->save();
    }

    /**
     * 新增数据化
     * @param array $param
     * @return int|string
     * @author LWW
     */
    public static function addAiMoney(array $param)
    {
        return self::create($param);
    }

    /**
     * 根据用户ID获取数据
     * @param array $userIds
     * @param array $filed
     * @return UserAiMoneyModel[]|array|\think\Collection|\think\model\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function listByUserIds(array $userIds, array $filed = ['*'])
    {
        return self::whereIn('user_id', $userIds)->field($filed)->select();
    }
}