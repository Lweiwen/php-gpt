<?php

namespace app\controller\index;

use app\BaseController;
use app\services\gpt\ChatService;
use app\validate\index\ChatGroupValidata;

class Chat extends BaseController
{
    /**
     * 获取分组
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function getGroupList()
    {
        $userId = $this->request->uid();
        list($param['title']) = $this->request->getMore(
            [
                ['keyword', ''],
            ],
            true
        );
        list($limit, $offset) = $this->pagination();
        return $this->apiResponse((new ChatService())->getGroupList($userId, $limit, $offset, $param));
    }

    /**
     * 编辑分组信息
     * @param string $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function editGroup(string $id)
    {
        $post = $this->request->postMore(
            [
                ['title', ''],
            ]
        );
        $this->validate(
            $post,
            ChatGroupValidata::class,
            'group_edit'
        );
        $userId = $this->request->uid();
        $id = maxCodeToId($id);
        (new ChatService())->editGroup($userId, $id, $post);
        return $this->apiResponse();
    }

    /**
     * 删除分组信息
     * @param string $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function delGroup(string $id)
    {
        $userId = $this->request->uid();
        $id = maxCodeToId($id);
        (new ChatService())->delGroup($userId, $id);
        return $this->apiResponse();
    }

    /**
     * 获取推送信息列表
     * @param string $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function getGroupChatMsg(string $id)
    {
        $userId = $this->request->uid();
        $id = maxCodeToId($id);
        return $this->apiResponse((new ChatService())->getGroupChatMsg($userId, $id));
    }

    /**
     * 发送信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author LWW
     */
    public function sendText()
    {
        $post = $this->request->postMore(
            [['group_id', 0], ['message', ''], ['message_id', 0], ['chat_model', 'gpt35']]
        );
        $userId = $this->request->uid();
        if (!empty($post['message_id'])) {
            $post['message_id'] = maxCodeToId($post['message_id']);
        }
        if (!empty($post['group_id'])) {
            $post['group_id'] = maxCodeToId($post['group_id']);
        }
        (new ChatService())->sendText($userId, $post['message'], $post['message_id'], $post['group_id'],$post['chat_model']);
    }
}