<?php

namespace app\model;

use think\model\concern\SoftDelete;

class ChatMsgModel extends BaseModel
{
    use SoftDelete;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'chat_msg';

    /**
     * 新增信息
     * @param array $data
     * @return ChatMsgModel|\think\Model
     * @author LWW
     */
    public static function addMsg(array $data)
    {
        return self::create($data);
    }

    /**
     * 更新回复内容
     * @param ChatMsgModel $obj
     * @param string $response
     * @return bool
     * @author LWW
     */
    public static function updateMsg(self $obj, string $response)
    {
        $obj->response = $response;
        $obj->response_time = time();
        return $obj->save();
    }

    /**
     * 返回数据
     * @param int $groupId
     * @param int $userId
     * @param array $order
     * @param int $limit
     * @param int $offset
     * @return ChatMsgModel[]|array|\think\Collection|\think\db\Query[]|\think\model\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function getByGroupIdAndUserId(
        int $groupId,
        int $userId,
        array $order = ['id' => 'desc'],
        int $limit = 0,
        int $offset = 0
    ) {
        $obj = self::where('group_id', '=', $groupId)->where('user_id', '=', $userId);
        foreach ($order as $key => $val) {
            $obj->order($key, $val);
        }
        if (!empty($limit)) {
            $obj->limit($offset, $limit);
        }
        return $obj->select();
    }

    /**
     * 获取列表数据
     * @param array $param
     * @param array $order
     * @param int $limit
     * @param int $offset
     * @return ChatMsgModel[]|array|\think\Collection|\think\db\Query[]|\think\model\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function listForGroupMsg(array $param, array $order, int $limit = 0, int $offset = 0)
    {
        $where = [];
        if (isset($param['group_id'])) {
            $where[] = ['group_id', '=', $param['group_id']];
        }
        if (isset($param['user_id'])) {
            $where[] = ['user_id', '=', $param['user_id']];
        }
        if (isset($param['max_id']) && $param['max_id'] > 0) {
            $where[] = ['id', '<=', $param['max_id']];
        }
        if (isset($param['begin_time']) && !empty($param['begin_time'])) {
            $where[] = ['create_time', '>=', $param['begin_time']];
        }
        $obj = self::where($where);
        foreach ($order as $key => $val) {
            $obj->order($key, $val);
        }
        if (!empty($limit)) {
            $obj->limit($offset,$limit);
        }
        return $obj->select();
    }

    /**
     * 根据ID查找数据
     * @param int $msgId
     * @param int $groupId
     * @return ChatMsgModel|array|mixed|\think\db\Query|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public static function findById(int $msgId, int $groupId)
    {
        return self::where('id', $msgId)->find();
    }
}