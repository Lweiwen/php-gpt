<?php

namespace app\model;

use think\model\concern\SoftDelete;

class ChatMsgGroupModel extends BaseModel
{
    use SoftDelete;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'chat_msg_group';

    /**
     * 处理筛选条件
     * @param array $param
     * @return ChatMsgGroupModel|\think\db\Query
     * @author LWW
     */
    public static function dealForSelect(array $param)
    {
        $where = [];
        if (isset($param['title']) && !empty($param['title'])) {
            $where[] = ['title', 'like', '%' . $param['title'] . '%'];
        }
        if (isset($param['user_id']) && !empty($param['user_id'])) {
            $where[] = ['user_id', '=',$param['user_id']];
        }
        return self::where($where);
    }

    /**
     * 返回数量
     * @param array $params
     * @return int|\think\db\Query
     * @throws \think\db\exception\DbException
     * @author LWW
     */
    public static function countForGroup(array $params)
    {
        $obj = self::dealForSelect($params);
        return $obj->count();
    }

    /**
     * 列表
     * @param int $limit
     * @param int $offset
     * @param array $orderBy
     * @param array $params
     * @return GoodModel[]|array|\think\Collection|\think\db\Query[]|\think\model\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function listForGroup(int $limit, int $offset, array $orderBy = [], array $params = [])
    {
        $obj = self::dealForSelect($params);
        if (!empty($orderBy)) {
            foreach ($orderBy as $key => $val) {
                $obj->order($key, $val);
            }
        }
        return $obj->limit($offset, $limit)->select();
    }

    /**
     * 新增分组
     * @param array $data
     * @return int|string
     * @author LWW
     */
    public static function addGroup(array $data)
    {
        return self::insertGetId($data);
    }

    /**
     * 根据用户ID+分组id获取数据
     * @param int $userId
     * @param int $groupId
     * @return ChatMsgGroupModel|\think\db\Query
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findByUserIdAndGroupId(int $userId, int $groupId)
    {
        return self::where('id', $groupId)->where('user_id', $userId)->find();
    }

    /**
     * 删除分组信息
     * @param ChatMsgGroupModel $obj
     * @return bool
     * @author LWW
     */
    public static function del(self $obj)
    {
        return $obj->delete();
    }

    /**
     * 编辑信息
     * @param ChatMsgGroupModel $obj
     * @param array $param
     * @return bool
     * @author LWW
     */
    public static function editGroup(self $obj, array $param)
    {
        foreach ($param as $key => $val) {
            $obj->$key = $val;
        }
        return $obj->save();
    }
}